<?php

namespace App\Services\Orders;

use App\Models\Order;
use Exception;

class OrderValidationService
{
    public function validate(array $data): void
    {
        $this->validateProduct($data);
        $this->validateQuantity($data);
        $this->validateBusinessRules($data);
    }

    private function validateProduct(array $data): void
    {
        if (!array_key_exists($data['product'], Order::PRICES)) {
            throw new Exception('Produto inválido.');
        }
    }

    private function validateQuantity(array $data): void
    {
        if ($data['quantity'] <= 0) {
            throw new Exception('Quantidade deve ser maior que zero.');
        }

        if ($data['quantity'] > 100) {
            throw new Exception('Quantidade máxima permitida é 100.');
        }
    }

    private function validateBusinessRules(array $data): void
    {
        // Exemplo:
        // validar horário
        // validar limite diário
        // validar estoque
        // validar usuário bloqueado
    }
}
