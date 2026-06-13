<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    /**
     * Max lines read from the end of the log file.
     */
    private const MAX_LINES = 1000;

    /**
     * Show the import/Shopify log entries in the dashboard.
     */
    public function index(Request $request)
    {
        $logFile = storage_path('logs/import.log');
        $filterLevel = strtolower((string) $request->query('level', ''));

        $entries = $this->parseLog($logFile);

        if (in_array($filterLevel, ['info', 'warning', 'error'], true)) {
            $entries = array_filter($entries, fn ($e) => strtolower($e['level']) === $filterLevel);
        }

        // Newest first.
        $entries = array_reverse(array_values($entries));

        return view('dashboard.logs', [
            'entries' => $entries,
            'filterLevel' => $filterLevel,
            'logExists' => file_exists($logFile),
        ]);
    }

    /**
     * Clear the import log file.
     */
    public function clear()
    {
        $logFile = storage_path('logs/import.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return redirect()->route('dashboard.logs')->with('success', 'Import log cleared.');
    }

    /**
     * Parse a Laravel single-file log into structured entries.
     *
     * @return array<int, array{datetime:string, level:string, message:string}>
     */
    private function parseLog(string $logFile): array
    {
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        // Only keep the tail to stay fast on large files.
        if (count($lines) > self::MAX_LINES) {
            $lines = array_slice($lines, -self::MAX_LINES);
        }

        $entries = [];
        // Matches: [2026-06-13 11:27:33] local.ERROR: message
        $pattern = '/^\[(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\S+\.(?<level>[A-Z]+):\s+(?<message>.*)$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $m)) {
                $entries[] = [
                    'datetime' => $m['datetime'],
                    'level' => $m['level'],
                    'message' => $m['message'],
                ];
            } elseif (!empty($entries)) {
                // Continuation line (stack trace / context) -> append to last entry.
                $entries[count($entries) - 1]['message'] .= "\n" . $line;
            }
        }

        return $entries;
    }
}
