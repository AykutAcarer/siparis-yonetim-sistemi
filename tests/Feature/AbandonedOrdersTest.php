<?php

namespace Tests\Feature;

use App\Services\Orders\GoogleSheetsOrderRepository;
use RuntimeException;
use Tests\TestCase;

class AbandonedOrdersTest extends TestCase
{
    public function test_it_returns_abandoned_orders_with_filters_meta(): void
    {
        $repository = $this->createMock(GoogleSheetsOrderRepository::class);

        $repository->method('getAbandonedOrders')->willReturn([
            'rows' => [
                [
                    'orderId' => 'AB-1',
                    'timestamp' => '2025-10-15 13:45',
                    'orderStatus' => 'Abandoned',
                    'source' => 'whatsapp',
                ],
            ],
            'headers' => [],
            'source_column_present' => true,
        ]);

        $this->app->instance(GoogleSheetsOrderRepository::class, $repository);

        $response = $this->getJson('/api/orders/abandoned');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.orderId', 'AB-1')
            ->assertJsonPath('meta.sourceColumnPresent', true)
            ->assertJsonPath('meta.usesMockData', false);
    }

    public function test_it_uses_mock_data_when_google_fails(): void
    {
        $repository = $this->createMock(GoogleSheetsOrderRepository::class);
        $repository->method('getAbandonedOrders')->willThrowException(new RuntimeException('fail'));

        $this->app->instance(GoogleSheetsOrderRepository::class, $repository);

        $response = $this->getJson('/api/orders/abandoned');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.orderId', '202510151345-009')
            ->assertJsonPath('meta.usesMockData', true);
    }
}
