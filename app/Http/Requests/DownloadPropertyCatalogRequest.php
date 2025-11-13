<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadPropertyCatalogRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('language')) {
            $this->merge(['language' => 'en-US']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'string'],
            'supply_source' => ['required', 'in:expedia,vrbo'],
            'billing_terms' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string'],
            'partner_point_of_sale' => ['nullable', 'string'],
            'platform_name' => ['nullable', 'string'],
        ];
    }
}
