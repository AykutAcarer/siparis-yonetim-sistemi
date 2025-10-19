<?php

namespace App\Services\Orders;

use Illuminate\Support\Facades\Storage;

class DispatchedOrderStore
{
    private const FILE_NAME = 'dispatches.json';

    /**
     * @return array{status: string, dispatched_at: string}|null
     */
    public function statusFor(string $orderId): ?array
    {
        $store = $this->readStore();

        if (! array_key_exists($orderId, $store)) {
            return null;
        }

        /** @var array{status: string, dispatched_at: string} $status */
        $status = $store[$orderId];

        return $status;
    }

    /**
     * @return array{status: string, dispatched_at: string}
     */
    public function markDispatched(string $orderId): array
    {
        $store = $this->readStore();

        $status = [
            'status' => 'Dispatched',
            'dispatched_at' => now()->toIso8601String(),
        ];

        $store[$orderId] = $status;

        $this->writeStore($store);

        return $status;
    }

    /**
     * @return array<string, array{status: string, dispatched_at: string}>
     */
    public function all(): array
    {
        return $this->readStore();
    }

    /**
     * @return array<string, array{status: string, dispatched_at: string}>
     */
    private function readStore(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::FILE_NAME)) {
            return [];
        }

        $contents = $disk->get(self::FILE_NAME);

        if ($contents === null) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, array{status: string, dispatched_at: string}> $store */
        $store = $decoded;

        return $store;
    }

    /**
     * @param  array<string, array{status: string, dispatched_at: string}>  $store
     */
    private function writeStore(array $store): void
    {
        $payload = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return;
        }

        Storage::disk('local')->put(self::FILE_NAME, $payload);
    }
}

