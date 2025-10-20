<?php

namespace App\Services\Orders;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderDataService
{
    public function __construct(
        private readonly GoogleSheetsOrderRepository $sheetsRepository,
        private readonly MockOrderRepository $mockRepository,
        private readonly DispatchedOrderStore $dispatchStore,
    ) {
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getCompletedOrders(?string $channel = null): array
    {
        $context = $this->resolveChannelContext($channel);

        $result = $this->fetchWithFallback(
            fn () => $this->sheetsRepository->getCompletedOrders(
                $context['spreadsheet_id'],
                $context['completed_range']
            ),
            fn () => $this->mockRepository->getCompletedOrders()
        );

        $orders = collect($result['rows'])
            ->map(fn (array $row) => $this->transformCompletedRow($row))
            ->values()
            ->all();

        return [
            'data' => $orders,
            'meta' => [
                'usesMockData' => $result['used_fallback'],
                'sourceColumnPresent' => $result['source_column_present'],
                'fetchedAt' => now()->toIso8601String(),
                'dispatchedIds' => array_keys($this->dispatchStore->all()),
                'channel' => $context['channel'],
                'requestedChannel' => $context['requested'],
                'channelFallback' => $context['channel'] !== ($context['requested'] ?? $context['channel']),
            ],
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getAbandonedOrders(?string $channel = null): array
    {
        $context = $this->resolveChannelContext($channel);

        $result = $this->fetchWithFallback(
            fn () => $this->sheetsRepository->getAbandonedOrders(
                $context['spreadsheet_id'],
                $context['abandoned_range']
            ),
            fn () => $this->mockRepository->getAbandonedOrders()
        );

        $orders = collect($result['rows'])
            ->map(fn (array $row) => $this->transformAbandonedRow($row))
            ->values()
            ->all();

        return [
            'data' => $orders,
            'meta' => [
                'usesMockData' => $result['used_fallback'],
                'sourceColumnPresent' => $result['source_column_present'],
                'fetchedAt' => now()->toIso8601String(),
                'channel' => $context['channel'],
                'requestedChannel' => $context['requested'],
                'channelFallback' => $context['channel'] !== ($context['requested'] ?? $context['channel']),
            ],
        ];
    }

    /**
     * @param  callable(): array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}  $primary
     * @param  callable(): array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}  $fallback
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool, used_fallback: bool}
     */
    private function fetchWithFallback(callable $primary, callable $fallback): array
    {
        try {
            $payload = $primary();

            return $payload + ['used_fallback' => false];
        } catch (Exception $exception) {
            Log::warning('Primary data source failed, switching to mock data', [
                'exception' => $exception->getMessage(),
            ]);

            $payload = $fallback();

            return $payload + ['used_fallback' => true];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function transformCompletedRow(array $row): array
    {
        $orderId = (string) Arr::get($row, 'orderId', '');
        $customerName = trim((string) Arr::get($row, 'customerName', ''));
        $customerSurname = trim((string) Arr::get($row, 'customerSurname', ''));
        $timestamp = $this->parseTimestamp(Arr::get($row, 'timestamp'));
        $price = $this->parseNumeric(Arr::get($row, 'totalPrice'));

        $statusData = $this->dispatchStore->statusFor($orderId);

        return [
            'orderId' => $orderId,
            'timestamp' => $timestamp?->toIso8601String(),
            'timestampDisplay' => $timestamp?->format('Y-m-d H:i'),
            'customerName' => $customerName,
            'customerSurname' => $customerSurname,
            'customerFullName' => trim($customerName.' '.$customerSurname),
            'customerPhone' => (string) Arr::get($row, 'customerPhone', ''),
            'customerAddress' => (string) Arr::get($row, 'customerAddress', ''),
            'paymentType' => (string) Arr::get($row, 'paymentType', ''),
            'totalPrice' => $price,
            'status' => $statusData['status'] ?? 'Pending',
            'dispatchedAt' => $statusData['dispatched_at'] ?? null,
            'source' => Arr::get($row, 'source'),
            'raw' => [
                'timestamp' => Arr::get($row, 'timestamp'),
                'totalPrice' => Arr::get($row, 'totalPrice'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function transformAbandonedRow(array $row): array
    {
        $timestamp = $this->parseTimestamp(Arr::get($row, 'timestamp'));

        return [
            'orderId' => (string) Arr::get($row, 'orderId', ''),
            'timestamp' => $timestamp?->toIso8601String(),
            'timestampDisplay' => $timestamp?->format('Y-m-d H:i'),
            'orderStatus' => (string) Arr::get($row, 'orderStatus', ''),
            'source' => Arr::get($row, 'source'),
            'raw' => [
                'timestamp' => Arr::get($row, 'timestamp'),
            ],
        ];
    }

    /**
     * @param  ?string  $value
     */
    private function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param  mixed  $value
     */
    private function parseNumeric(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace([',', ' '], ['.', ''], $value);

            return is_numeric($normalized) ? (float) $normalized : null;
        }

        return null;
    }

    /**
     * @return array{spreadsheet_id: ?string, completed_range: ?string, abandoned_range: ?string}
     */
    private function resolveChannelContext(?string $channel): array
    {
        $config = config('services.google_sheets');
        $channels = $config['channels'] ?? [];
        $defaultKey = strtolower($config['default_channel'] ?? 'telegram');

        if (! isset($channels[$defaultKey])) {
            $channels[$defaultKey] = [
                'spreadsheet_id' => $config['spreadsheet_id'] ?? null,
                'completed_range' => $config['completed_range'] ?? null,
                'abandoned_range' => $config['abandoned_range'] ?? null,
            ];
        }

        $requestedKey = $channel !== null ? strtolower($channel) : null;
        $resolvedKey = $requestedKey ?? $defaultKey;

        $selected = $channels[$resolvedKey] ?? null;

        if (! is_array($selected) || empty($selected['spreadsheet_id'])) {
            $resolvedKey = $defaultKey;
            $selected = $channels[$resolvedKey] ?? null;
        }

        if (! is_array($selected) || empty($selected['spreadsheet_id'])) {
            throw new RuntimeException('Google Sheets spreadsheet id is not configured for the default channel.');
        }

        $default = $channels[$defaultKey] ?? [];

        $spreadsheetId = $selected['spreadsheet_id'] ?? $default['spreadsheet_id'] ?? null;

        if (empty($spreadsheetId)) {
            throw new RuntimeException('Google Sheets spreadsheet id is not configured for channel: '.$resolvedKey);
        }

        return [
            'requested' => $requestedKey,
            'channel' => $resolvedKey,
            'spreadsheet_id' => $spreadsheetId,
            'completed_range' => $selected['completed_range'] ?? $default['completed_range'] ?? null,
            'abandoned_range' => $selected['abandoned_range'] ?? $default['abandoned_range'] ?? null,
        ];
    }
}
