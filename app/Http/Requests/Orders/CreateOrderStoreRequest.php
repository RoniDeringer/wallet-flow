<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer'      => ['nullable', 'string', 'max:255'],
            'product'       => ['nullable', 'string', 'max:255'],
            'quantity'      => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer.required' => 'O nome do cliente é obrigatório.',
            'customer.string' => 'O nome do cliente deve ser um texto.',

            'product.required' => 'O produto é obrigatório.',
            'product.string' => 'O produto deve ser um texto.',

            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser no mínimo 1.',

            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço não pode ser negativo.',
        ];
    }
}
