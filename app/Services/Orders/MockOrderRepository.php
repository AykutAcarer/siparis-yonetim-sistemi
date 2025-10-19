<?php

namespace App\Services\Orders;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MockOrderRepository
{
    private const COMPLETED_FILE = 'mock/completed.json';
    private const ABANDONED_FILE = 'mock/abandoned.json';

    /**
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    public function getCompletedOrders(): array
    {
        $records = $this->readRecords(self::COMPLETED_FILE);

        return $this->formatRecords($records);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    public function getAbandonedOrders(): array
    {
        $records = $this->readRecords(self::ABANDONED_FILE);

        return $this->formatRecords($records);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    private function formatRecords(array $records): array
    {
        $headers = $this->extractHeaders($records);

        $sourceColumnPresent = collect($headers)
            ->contains(fn (string $header) => strtolower($header) === 'source');

        return [
            'rows' => $records,
            'headers' => $headers,
            'source_column_present' => $sourceColumnPresent,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRecords(string $relativePath): array
    {
        $path = storage_path('app/'.$relativePath);

        if (! file_exists($path)) {
            Log::warning('Mock data file missing', ['path' => $path]);

            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            Log::warning('Unable to read mock data file', ['path' => $path]);

            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            Log::warning('Mock data file is not valid JSON', ['path' => $path]);

            return [];
        }

        /** @var array<int, array<string, mixed>> $records */
        $records = array_map(
            fn ($row) => is_array($row) ? $row : [],
            Arr::wrap($decoded)
        );

        return $records;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, string>
     */
    private function extractHeaders(array $records): array
    {
        $headers = [];

        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (! in_array($key, $headers, true)) {
                    $headers[] = $key;
                }
            }
        }

        return $headers;
    }
}

