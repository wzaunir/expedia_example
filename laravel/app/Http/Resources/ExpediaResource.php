<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpediaResource extends JsonResource
{
    /**
     * Disable default "data" wrapping.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return $this->resource;
    }
}
