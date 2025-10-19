<?php

namespace Tests\Feature;

use App\Services\Orders\GoogleSheetsOrderRepository;
use RuntimeException;
use Tests\TestCase;

class CompletedOrdersTest extends TestCase
{
    public function test_it_returns_transformed_orders_from_primary_source(): void
    {
        $repository = $this->createMock(GoogleSheetsOrderRepository::class);

        $repository->method('getCompletedOrders')->willReturn([
            'rows' => [
                [
                    'orderId' => 'A-100',
                    'timestamp' => '2025-10-15 14:32',
                    'customerName' => 'Ali',
                    'customerSurname' => 'Kaya',
                    'customerPhone' => '+491234567890',
                    'customerAddress' => 'Berlin',
                    'paymentType' => 'Card',
                    'totalPrice' => 59.99,
                    'source' => 'telegram',
                ],
            ],
            'headers' => [],
            'source_column_present' => true,
        ]);

        $this->app->instance(GoogleSheetsOrderRepository::class, $repository);

        $response = $this->getJson('/api/orders/completed');

        $response
            ->assertOk()
            ->assertJsonPath('meta.usesMockData', false)
            ->assertJsonPath('meta.sourceColumnPresent', true)
            ->assertJsonPath('data.0.orderId', 'A-100')
            ->assertJsonPath('data.0.customerFullName', 'Ali Kaya')
            ->assertJsonPath('data.0.status', 'Pending')
            ->assertJsonStructure([
                'data' => [
                    [
                        'orderId',
                        'timestamp',
                        'customerFullName',
                        'paymentType',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_it_falls_back_to_mock_data_when_google_sheets_fails(): void
    {
        $repository = $this->createMock(GoogleSheetsOrderRepository::class);

        $repository->method('getCompletedOrders')->willThrowException(new RuntimeException('Boom'));

        $this->app->instance(GoogleSheetsOrderRepository::class, $repository);

        $response = $this->getJson('/api/orders/completed');

        $response
            ->assertOk()
            ->assertJsonPath('meta.usesMockData', true)
            ->assertJsonPath('data.0.orderId', '202510151432-001');
    }
}
