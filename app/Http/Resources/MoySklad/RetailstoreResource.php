<?php

namespace App\Http\Resources\MoySklad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetailstoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => 123,
            'enabled' => $this->active,
            'id_crm' => $this->id,
            'sorting' => 'desc',
            'name' => $this->name,
            'latitude' => 'none',
            'longitude' => 'none',
            'address' => $this->address ?? '',
        ];
    }
}
