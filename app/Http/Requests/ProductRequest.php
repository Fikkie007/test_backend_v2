<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
        ];

        if ($this->isMethod('post')) {
            $rules['images'] = ['required', 'array', 'min:1'];
        } else {
            $rules['images'] = ['sometimes', 'array'];
        }

        $rules['images.*'] = ['string', 'regex:#^\d{4}/\d{2}/\d{2}/[^\\\\/]+$#', 'not_regex:#\.\.|^/#'];

        return $rules;
    }
}
