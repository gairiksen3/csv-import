<?php

namespace App\Jobs;

use App\Jobs\SyncImportProductsToShopify;
use App\Models\CsvImport;
use App\Models\Product;
use App\Notifications\ImportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $csvImport;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(CsvImport $csvImport, $filePath)
    {
        $this->csvImport = $csvImport;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = Log::channel('import');

        try {
            $this->csvImport->update(['status' => 'processing']);

            $log->info('Import started', [
                'import_id' => $this->csvImport->id,
                'user_id' => $this->csvImport->user_id,
                'file' => $this->csvImport->file_name,
            ]);

            $file = fopen($this->filePath, 'r');
            if (!$file) {
                throw new \Exception('Could not open file');
            }

            // Read header
            $header = fgetcsv($file);
            if (!$header) {
                throw new \Exception('Invalid CSV file - no header found');
            }

            $rowCount = 0;
            $importedCount = 0;
            $failedCount = 0;
            $errors = [];
            $productIds = [];

            while (($row = fgetcsv($file)) !== false) {
                $rowCount++;

                try {
                    if (count($row) !== count($header)) {
                        throw new \Exception("Row $rowCount has mismatched columns");
                    }

                    // Map CSV headers to database field names
                    $mappedData = $this->mapCsvToDatabase($header, $row);
                    $mappedData['user_id'] = $this->csvImport->user_id;

                    // Clean and transform data
                    $data = $this->cleanData($mappedData);

                    // Upsert by SKU (scoped to this user): update an existing
                    // product when the SKU matches, otherwise create a new one.
                    $sku = trim((string) ($data['variant_sku'] ?? ''));
                    $existing = $sku !== ''
                        ? Product::where('user_id', $this->csvImport->user_id)
                            ->where('variant_sku', $sku)
                            ->first()
                        : null;

                    if ($existing) {
                        // Re-sync to Shopify on update; reset the sync status.
                        $data['shopify_status'] = 'pending';
                        $existing->update($data);
                        $product = $existing;
                    } else {
                        $product = Product::create($data);
                    }

                    $importedCount++;

                    // Collect ids; the Shopify sync is dispatched once, after
                    // the parsing loop completes (see below).
                    $productIds[] = $product->id;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Row $rowCount: " . $e->getMessage();

                    $log->warning('Import row failed', [
                        'import_id' => $this->csvImport->id,
                        'row' => $rowCount,
                        'error' => $e->getMessage(),
                    ]);

                    // Keep only last 100 errors
                    if (count($errors) > 100) {
                        array_shift($errors);
                    }
                }
            }

            fclose($file);

            // Update CSV import record
            $this->csvImport->update([
                'status' => 'completed',
                'total_rows' => $rowCount,
                'imported_rows' => $importedCount,
                'failed_rows' => $failedCount,
                'error_message' => count($errors) > 0 ? implode("\n", $errors) : null,
            ]);

            $log->info('Import completed', [
                'import_id' => $this->csvImport->id,
                'total' => $rowCount,
                'imported' => $importedCount,
                'failed' => $failedCount,
            ]);

            // Notify the user if some rows could not be imported into the DB.
            if ($failedCount > 0) {
                $this->notifyUser(
                    'warning',
                    'Import completed with errors',
                    "{$this->csvImport->file_name}: {$importedCount} imported, {$failedCount} row(s) failed."
                );
            }

            // Delete the uploaded file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

            // Now that all rows are stored, push them to Shopify in a single
            // background job (one dispatch instead of one per row).
            if (!empty($productIds)) {
                SyncImportProductsToShopify::dispatch($productIds);
            }

        } catch (\Exception $e) {
            $this->csvImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $log->error('Import failed', [
                'import_id' => $this->csvImport->id,
                'error' => $e->getMessage(),
            ]);

            $this->notifyUser(
                'error',
                'Import failed',
                "{$this->csvImport->file_name}: " . $e->getMessage()
            );

            // Delete the uploaded file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
    }

    /**
     * Send a database notification to the user who started the import.
     */
    private function notifyUser(string $level, string $title, string $message): void
    {
        $user = $this->csvImport->user;
        if ($user) {
            $user->notify(new ImportNotification($level, $title, $message, route('dashboard.csv-upload')));
        }
    }

    /**
     * Map CSV headers to database field names
     */
    private function mapCsvToDatabase($header, $row)
    {
        $headerMapping = [
            'Handle' => 'handle',
            'Title' => 'title',
            'Body HTML' => 'body_html',
            'Vendor' => 'vendor',
            'Product Type' => 'product_type',
            'Tags' => 'tags',
            'Published' => 'published',
            'Variant SKU' => 'variant_sku',
            'Variant Price' => 'variant_price',
            'Variant Compare At Price' => 'variant_compare_at_price',
            'Variant Requires Shipping' => 'variant_requires_shipping',
            'Variant Taxable' => 'variant_taxable',
            'Variant Inventory Tracker' => 'variant_inventory_tracker',
            'Variant Inventory Qty' => 'variant_inventory_qty',
            'Variant Inventory Policy' => 'variant_inventory_policy',
            'Variant Fulfillment Service' => 'variant_fulfillment_service',
            'Variant Weight' => 'variant_weight',
            'Variant Weight Unit' => 'variant_weight_unit',
            'Image Src' => 'image_src',
            'Image Position' => 'image_position',
            'Image Alt Text' => 'image_alt_text',
        ];

        $mappedData = [];
        foreach ($header as $index => $headerName) {
            if (isset($headerMapping[$headerName])) {
                $fieldName = $headerMapping[$headerName];
                $mappedData[$fieldName] = $row[$index] ?? '';
            }
        }

        return $mappedData;
    }

    /**
     * Clean and transform CSV data
     */
    private function cleanData($data)
    {
        // Remove leading/trailing whitespace
        foreach ($data as $key => $value) {
            $data[$key] = trim($value ?? '');
        }

        // Convert boolean-like values
        $booleanFields = ['published', 'variant_requires_shipping', 'variant_taxable'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = strtoupper($data[$field]) === 'TRUE' ? 1 : 0;
            }
        }

        // Convert numeric fields
        $numericFields = ['variant_price', 'variant_compare_at_price', 'variant_weight', 'variant_inventory_qty', 'image_position'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = is_numeric($data[$field]) ? $data[$field] : null;
            } else {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
