<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChainsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer'],
            'token' => ['nullable', 'string'],
        ];
    }
}
