<?php

namespace App\Services\Orders;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DispatchOrderService
{
    private const MAX_ATTEMPTS = 3;
    private const BACKOFF_BASE_MS = 500;

    public function __construct(
        private readonly DispatchedOrderStore $dispatchStore
    ) {
    }

    /**
     * @return array{orderId: string, status: string, dispatchedAt: string, attempts: int}
     */
    public function dispatch(string $orderId): array
    {
        $orderId = trim($orderId);

        if ($orderId === '') {
            throw new RuntimeException('Order ID can not be empty.');
        }

        $url = config('services.webhooks.dispatch.url');

        //$chatId = config('services.webhooks.dispatch.chat_id');
        $chatId = $this->extractChatIdFromOrderId($orderId);
        //if ($url === null || trim($url) === '') {
        //    throw new RuntimeException('Dispatch webhook URL is not configured.');
        //}

        if ($chatId === null || trim((string) $chatId) === '') {
            throw new RuntimeException('Dispatch chat ID is not configured.');
        }

        $lastResponse = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->fireWebhook($url, [
                'orderId' => $orderId,
                'chatId' => (string) $chatId,
            ]);

            $this->logAttempt($orderId, $attempt, $response);

            if ($response->successful()) {
                $status = $this->dispatchStore->markDispatched($orderId);

                return [
                    'orderId' => $orderId,
                    'status' => $status['status'],
                    'dispatchedAt' => $status['dispatched_at'],
                    'attempts' => $attempt,
                ];
            }

            $lastResponse = $response;

            if ($attempt < self::MAX_ATTEMPTS) {
                $this->sleepForAttempt($attempt);
            }
        }

        $statusCode = $lastResponse?->status();
        $message = $lastResponse?->json('message') ?? $lastResponse?->body() ?? 'Unknown error';

        throw new RuntimeException(sprintf(
            'Dispatch webhook failed for order %s (status %s): %s',
            $orderId,
            $statusCode ?? 'n/a',
            $message
        ));
    }

    /**
     * @param  array<string, string>  $payload
     */
    private function fireWebhook(string $url, array $payload): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);
    }

    private function logAttempt(string $orderId, int $attempt, Response $response): void
    {
        Log::info('Dispatch webhook attempt', [
            'orderId' => $orderId,
            'attempt' => $attempt,
            'status' => $response->status(),
            'success' => $response->successful(),
        ]);
    }

    private function sleepForAttempt(int $attempt): void
    {
        $delayMs = self::BACKOFF_BASE_MS * (2 ** ($attempt - 1));
        usleep($delayMs * 1000);
    }

    /**
 * Extract chat ID from order ID format: "chatId_timestamp"
 * Example: "7948113920_20251019120726" -> "7948113920"
 */
private function extractChatIdFromOrderId(string $orderId): string
{
    $parts = explode('_', $orderId);
    
    if (count($parts) < 2) {
        throw new RuntimeException(sprintf(
            'Invalid order ID format. Expected "chatId_timestamp", got: %s',
            $orderId
        ));
    }
    
    $chatId = $parts[0];
    
    if ($chatId === '' || !is_numeric($chatId)) {
        throw new RuntimeException(sprintf(
            'Invalid chat ID extracted from order ID: %s',
            $orderId
        ));
    }
    
    return $chatId;
}

}
