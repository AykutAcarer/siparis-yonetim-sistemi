<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderDataService;
use Illuminate\Http\JsonResponse;

class CompletedOrdersController extends Controller
{
    public function __construct(
        private readonly OrderDataService $orderDataService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->orderDataService->getCompletedOrders()
        );
    }
}

