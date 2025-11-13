<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateItineraryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $json = $this->json()->all();

        if (!empty($json)) {
            $this->merge($json);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'phone' => ['required', 'array'],
            'phone.country_code' => ['required', 'string'],
            'phone.number' => ['required', 'string'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.given_name' => ['required', 'string'],
            'rooms.*.family_name' => ['required', 'string'],
            'payments' => ['nullable', 'array'],
            'payments.*.type' => ['required_with:payments', 'string'],
        ];
    }
}
