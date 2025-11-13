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
        $geojson = $this->json()->all();

        if (!empty($geojson)) {
            $this->merge(['geojson' => $geojson]);
        }

        if (!$this->has('include')) {
            $this->merge(['include' => 'property_ids']);
        }

        if (!$this->has('supply_source')) {
            $this->merge(['supply_source' => 'expedia']);
        }
    }

    public function rules(): array
    {
        return [
            'geojson' => ['required', 'array'],
            'include' => ['required', 'in:property_ids'],
            'supply_source' => ['required', 'in:expedia,vrbo'],
        ];
    }
}
