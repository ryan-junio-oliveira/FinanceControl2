<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'brand_label' => $this->brand_label,
            'credit_limit' => (float) $this->credit_limit,
            'closing_day' => $this->closing_day,
            'due_day' => $this->due_day,
            'active' => (bool) $this->active,
            'open_invoice' => (float) $this->open_invoice,
            'available_limit' => (float) $this->available_limit,
            'display_color' => $this->display_color,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ]),
            'holder' => $this->whenLoaded('holder', fn () => [
                'id' => $this->holder->id,
                'name' => $this->holder->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
