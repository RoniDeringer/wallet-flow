<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'O username deve conter apenas letras, números e underscore (_).',
        ];
    }
}