<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertiesByPolygonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'geojson' => $this->getContent(),
        ]);
    }

    public function rules(): array
    {
        return [
            'geojson' => ['required', 'string'],
            'include' => ['nullable', 'string'],
            'supply_source' => ['nullable', 'string'],
        ];
    }
}
