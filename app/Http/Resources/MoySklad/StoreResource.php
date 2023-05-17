<?php

namespace App\Http\Resources\MoySklad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'id_crm' => $this->id, // Код склада $this->code
            'position' => 'desc',
            'name' => $this->name,
            'latitude' => 'none',
            'longitude' => 'none',
        ];
    }
}
