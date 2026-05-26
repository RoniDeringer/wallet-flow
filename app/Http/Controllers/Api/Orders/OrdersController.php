<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;

use App\Http\Requests\Orders\CreateOrderStoreRequest;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Services\Orders\OrderValidationService;
use Exception;
use Illuminate\Http\JsonResponse;

class OrdersController extends Controller
{

    public function index(OrderService $orderService)
    {

        $orders = $orderService->listOrders();


        return response()->json([
            'message' => 'Ordens listadas com sucesso',
            'data'  => $orders,
        ], 201);
    }

    public function deliver(int $orderId, OrderService $orderService)
    {
        $orders = $orderService->deliverOrder($orderId);

        return response()->json([
            'message' => 'Pedido entregue com sucesso',
            'data'  => $orders,
        ], 201);
    }

    public function store(
        CreateOrderStoreRequest $request,
        OrderValidationService $validationService,
        OrderService $orderService
    ): JsonResponse {

        try {

            $validated = $request->validated();

            $validationService->validate($validated);

            $orders = $orderService->create($validated);


            return response()->json([
                'message'   => 'Pedido criado com sucesso.',
                'data'      => $orders,
            ], 201);
        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
