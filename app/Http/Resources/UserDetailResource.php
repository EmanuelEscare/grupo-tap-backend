<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->getKey(),
            'code' => $this->code,
            'user' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'profiles' => $this->profiles ?? [],
        ];
    }
}
