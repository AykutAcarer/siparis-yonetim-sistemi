<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DispatchOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_it_posts_to_dispatch_webhook_and_persists_status(): void
    {
        Config::set('services.webhooks.dispatch.url', 'https://example.com/webhook');
        Config::set('services.webhooks.dispatch.chat_id', '7948113920');

        Http::fake([
            'https://example.com/webhook' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson('/api/orders/ORDER-1/dispatch');

        $response
            ->assertOk()
            ->assertJsonPath('data.orderId', 'ORDER-1')
            ->assertJsonPath('data.status', 'Dispatched');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook'
                && $request['orderId'] === 'ORDER-1'
                && $request['chatId'] === '7948113920';
        });

        $this->assertTrue(Storage::disk('local')->exists('dispatches.json'));

        $payload = json_decode(Storage::disk('local')->get('dispatches.json'), true);

        $this->assertSame('Dispatched', $payload['ORDER-1']['status']);
    }

    public function test_it_returns_error_if_webhook_fails(): void
    {
        Config::set('services.webhooks.dispatch.url', 'https://example.com/webhook');
        Config::set('services.webhooks.dispatch.chat_id', '7948113920');

        Http::fake([
            'https://example.com/webhook' => Http::response(['message' => 'fail'], 500),
        ]);

        $response = $this->postJson('/api/orders/ORDER-2/dispatch');

        $response->assertStatus(422);
    }
}

