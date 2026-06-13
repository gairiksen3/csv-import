<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvImport;
use App\Models\CsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CsvImportController extends Controller
{
    /**
     * Upload CSV file
     */
    public function store(Request $request)
    {
        // Validate file
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ], [
            'csv_file.required' => 'Please select a CSV file to upload',
            'csv_file.file' => 'Please upload a valid file',
            'csv_file.mimes' => 'Only CSV files are allowed. Please upload a .csv file',
            'csv_file.max' => 'File size must not exceed 10MB',
        ]);

        try {
            // Ensure storage directory exists
            $storageDir = storage_path('app/csv_imports');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $file = $request->file('csv_file');
            $filePath = $file->store('csv_imports', 'local');
            $fullPath = Storage::disk('local')->path($filePath);

            // Validate CSV format by checking header
            $this->validateCsvFormat($fullPath);

            // Create CSV import record
            $csvImport = CsvImport::create([
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            // Dispatch background job
            ProcessCsvImport::dispatch($csvImport, $fullPath);

            return response()->json([
                'success' => true,
                'message' => 'CSV file uploaded successfully! Processing in background...',
                'import_id' => $csvImport->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check import status
     */
    public function checkStatus($importId)
    {
        try {
            $csvImport = CsvImport::findOrFail($importId);

            // Verify user owns this import
            if ($csvImport->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'status' => $csvImport->status,
                'total_rows' => $csvImport->total_rows,
                'imported_rows' => $csvImport->imported_rows,
                'failed_rows' => $csvImport->failed_rows,
                'error_message' => $csvImport->error_message,
                'created_at' => $csvImport->created_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import not found',
            ], 404);
        }
    }

    /**
     * Get user's import history
     */
    public function history()
    {
        $imports = CsvImport::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'imports' => $imports,
        ]);
    }

    /**
     * Validate CSV format
     */
    private function validateCsvFormat($filePath)
    {
        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new \Exception('Could not open CSV file');
        }

        $header = fgetcsv($file);
        fclose($file);

        if (!$header) {
            throw new \Exception('CSV file is empty or invalid');
        }

        // Required columns for Shopify CSV
        $requiredColumns = [
            'Handle', 'Title', 'Vendor', 'Product Type', 'Variant SKU',
            'Variant Price', 'Variant Inventory Qty'
        ];

        $headerLower = array_map('strtolower', $header);
        $requiredLower = array_map('strtolower', $requiredColumns);

        foreach ($requiredLower as $required) {
            if (!in_array($required, $headerLower)) {
                throw new \Exception("CSV file is missing required column: " . ucfirst(str_replace('_', ' ', $required)));
            }
        }
    }
}
