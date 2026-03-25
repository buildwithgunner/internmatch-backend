<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_name' => $this->company_name,
            'email'        => $this->email,
            'description'  => $this->description,
            'website'      => $this->website,
            'industry'     => $this->industry,
            'logo_url'     => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
            'is_verified'  => (bool)$this->is_verified,
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}
