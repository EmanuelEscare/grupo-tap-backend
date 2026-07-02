<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'section_ids' => $this->section_ids ?? [],
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'sections' => $this->sections ?? [],
        ];
    }
}
