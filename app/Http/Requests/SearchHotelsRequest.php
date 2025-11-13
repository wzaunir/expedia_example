<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchHotelsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cityId') && !$this->has('ancestor_id')) {
            $this->merge(['ancestor_id' => $this->input('cityId')]);
        }

        if ($this->has('include') && is_string($this->input('include'))) {
            $include = array_filter(array_map('trim', explode(',', $this->input('include'))));
            $this->merge(['include' => $include]);
        }

        if (!$this->has('include')) {
            $this->merge(['include' => ['property_ids']]);
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
            'language' => ['required', 'string'],
            'include' => ['required', 'array', 'min:1'],
            'include.*' => ['in:standard,details,property_ids,property_ids_expanded'],
            'ancestor_id' => ['nullable', 'integer'],
            'area' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
        ];
    }
}
