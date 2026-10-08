<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9_]{3,50}$/'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }
}
