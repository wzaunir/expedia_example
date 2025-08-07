<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InactivePropertiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'since' => ['required', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
        ];
    }
}
