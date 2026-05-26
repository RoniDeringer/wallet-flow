<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;

class OrderService
{
    private string $path = 'orders.json';

    public function listOrders(): array
    {

        $orders = $this->getOrders();

        $orders = $this->putTotalPrice($orders);

        return $orders;
    }

    public function deliverOrder(int $orderId): array
    {
        $orders = $this->getOrders();

        foreach ($orders as &$order) {

            if ($order['id'] === $orderId) {

                $order['status'] = Order::STATUS['FINISHED'];

                break;
            }
        }

        unset($order);

        $this->saveOrders($orders);

        return $this->putTotalPrice($orders);
    }

    public function create(array $data): array
    {
        $orders = $this->getOrders();

        $lastId = !empty($orders)
            ? max(array_column($orders, 'id'))
            : 0;

        $newOrder = [
            'id'            => $lastId + 1,
            'customer'      => $data['customer'],
            'product'       => $data['product'],
            'quantity'      => $data['quantity'],
            'price'         => Order::PRICES[$data['product']],
            'status'        => Order::STATUS['PENDING'],
            'created_at'    => now()->format('Y-m-d H:i:s'),
        ];

        $orders[] = $newOrder;

        $this->saveOrders($orders);

        return $orders;
    }

    private function getOrders(): array
    {
        if (!Storage::exists($this->path)) {
            Storage::put(
                $this->path,
                json_encode([], JSON_PRETTY_PRINT)
            );
        }

        $orders = json_decode(
            Storage::get($this->path),
            true
        );

        return is_array($orders)
            ? $orders
            : [];
    }

    private function saveOrders(array $orders): void
    {
        Storage::put(
            $this->path,
            json_encode(
                $orders,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    private function putTotalPrice(array $orders): array
    {
        foreach ($orders as &$order) {

            $amountTotal =
                $order['price'] * $order['quantity'];

            $order['amount_total'] = number_format(
                $amountTotal,
                2,
                '.',
                ''
            );
        }

        unset($order);

        return $orders;
    }
}
