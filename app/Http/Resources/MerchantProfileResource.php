<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'address' => $this->address,
            'description' => $this->description,
            'logo' => $this->logo,
            'banner' => $this->banner,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
        ];
    }
}
