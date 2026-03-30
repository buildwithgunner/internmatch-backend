<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruiterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'company_id'        => $this->company_id,
            'company_name'      => $this->company_name,
            'sector'            => $this->sector,
            'position'          => $this->position,
            'bio'               => $this->bio,
            'linkedin'          => $this->linkedin,
            'website'           => $this->website,
            'is_verified'       => (bool) $this->is_verified,
            'trust_score'       => $this->trust_score,
            'trust_level'       => $this->trust_level,
            'created_at'        => $this->created_at->toDateTimeString(),
            
            // Relationships
            'company'           => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}
