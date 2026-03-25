<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'role'              => $this->role,
            'phone'             => $this->phone,
            'profile_photo'      => $this->profile_photo ? asset('storage/' . $this->profile_photo) : null,
            'is_verified'       => (bool)$this->is_verified,
            
            // Computed fields often used in frontend
            'is_profile_complete' => $this->when(isset($this->is_profile_complete), $this->is_profile_complete),
            'profile_strength'    => $this->when(isset($this->profile_strength), $this->profile_strength),
            
            // Relationships
            'profile'           => $this->whenLoaded('profile'),
            'documents'         => $this->whenLoaded('documents'),
            
            'created_at'        => $this->created_at->toDateTimeString(),
        ];
    }
}
