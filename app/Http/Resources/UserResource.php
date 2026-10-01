<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->roleLabel(),
            'phone' => $this->phone,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'initials' => $this->initials(),
            'avatar_color' => $this->avatarColor(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
