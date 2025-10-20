<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompletedOrdersController extends Controller
{
    public function __construct(
        private readonly OrderDataService $orderDataService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $channel = $request->route('channel', $request->query('channel'));

        return response()->json(
            $this->orderDataService->getCompletedOrders($channel)
        );
    }
}
