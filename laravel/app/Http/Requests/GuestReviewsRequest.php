<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestReviewsRequest extends FormRequest
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
            'property_id' => ['required', 'integer'],
            'language' => ['required', 'string'],
            'token' => ['nullable', 'string'],
        ];
    }
}
