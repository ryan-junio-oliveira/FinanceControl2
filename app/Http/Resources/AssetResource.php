<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'kind' => $this->kind,
            'kind_label' => $this->kind_label,
            'institution' => $this->institution,
            'current_value' => (float) $this->current_value,
            'yield_percent' => $this->whenNotNull($this->yield_percent, fn () => (float) $this->yield_percent),
            'yield_base' => $this->yield_base,
            'yield_label' => $this->yield_label,
            'portfolio' => $this->whenLoaded('portfolio', fn () => [
                'id' => $this->portfolio->id,
                'name' => $this->portfolio->name,
            ]),
        ];
    }
}
