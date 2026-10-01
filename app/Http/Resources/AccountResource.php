<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'initial_balance' => (float) $this->initial_balance,
            'balance' => (float) $this->balance,
            'active' => (bool) $this->active,
            'bank' => $this->whenLoaded('bank', fn () => [
                'id' => $this->bank->id,
                'name' => $this->bank->name,
                'code' => $this->bank->code,
                'color' => $this->bank->color,
            ]),
            'display_color' => $this->display_color,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
