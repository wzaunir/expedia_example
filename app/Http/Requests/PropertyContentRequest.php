<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyContentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('property_id') && is_string($this->input('property_id'))) {
            $properties = array_filter(array_map('trim', explode(',', $this->input('property_id'))));
            $this->merge(['property_id' => $properties]);
        }

        if ($this->has('include') && is_string($this->input('include'))) {
            $includes = array_filter(array_map('trim', explode(',', $this->input('include'))));
            $this->merge(['include' => $includes]);
        }

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
            'property_id' => ['required', 'array', 'min:1', 'max:250'],
            'property_id.*' => ['required', 'integer'],
            'language' => ['required', 'string'],
            'supply_source' => ['required', 'in:expedia,vrbo'],
            'include' => ['nullable', 'array'],
            'include.*' => ['string'],
            'token' => ['nullable', 'string'],
        ];
    }
}
