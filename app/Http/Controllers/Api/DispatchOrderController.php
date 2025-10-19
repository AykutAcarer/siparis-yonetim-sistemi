<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orders\DispatchOrderService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DispatchOrderController extends Controller
{
    public function __construct(
        private readonly DispatchOrderService $dispatchOrderService
    ) {
    }

    public function __invoke(string $orderId): JsonResponse
    {
        try {
            $result = $this->dispatchOrderService->dispatch($orderId);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}

