<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('property_id') && is_string($this->input('property_id'))) {
            $properties = array_filter(array_map('trim', explode(',', $this->input('property_id'))));
            $this->merge(['property_id' => $properties]);
        }

        if ($this->has('occupancy') && is_string($this->input('occupancy'))) {
            $occupancy = array_filter(array_map('trim', explode(',', $this->input('occupancy'))));
            $this->merge(['occupancy' => $occupancy]);
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
            'checkin' => ['required', 'date_format:Y-m-d'],
            'checkout' => ['required', 'date_format:Y-m-d', 'after:checkin'],
            'language' => ['required', 'string'],
            'currency' => ['required', 'string', 'size:3'],
            'country_code' => ['required', 'string', 'size:2'],
            'occupancy' => ['required', 'array', 'min:1', 'max:8'],
            'occupancy.*' => ['required', 'string'],
            'rate_plan_count' => ['required', 'integer', 'between:1,250'],
            'sales_channel' => ['required', 'in:website,agent_tool,mobile_app,mobile_web,meta,cache'],
            'sales_environment' => ['required', 'in:hotel_package,hotel_only,loyalty'],
            'amenity_category' => ['nullable', 'array'],
            'amenity_category.*' => ['string'],
            'exclusion' => ['nullable', 'array'],
            'exclusion.*' => ['in:refundable_damage_deposit,card_on_file'],
            'token' => ['nullable', 'string'],
        ];
    }
}
