<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'objective' => $this->objective,
            'target_amount' => $this->whenNotNull($this->target_amount, fn () => (float) $this->target_amount),
            'total' => (float) $this->total,
            'progress' => $this->progress,
            'deadline' => $this->deadline?->format('Y-m-d'),
            'monthly_needed' => $this->whenNotNull($this->monthly_needed, fn () => (float) $this->monthly_needed),
        ];
    }
}
