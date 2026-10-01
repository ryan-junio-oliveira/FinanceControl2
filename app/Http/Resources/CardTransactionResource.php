<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'occurred_on' => $this->occurred_on?->format('Y-m-d'),
            'status' => $this->status,
            'kind' => $this->kind,
            'installment_number' => $this->installment_number,
            'installments_total' => $this->installments_total,
            'card' => $this->whenLoaded('card', fn () => [
                'id' => $this->card->id,
                'name' => $this->card->name,
            ]),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
