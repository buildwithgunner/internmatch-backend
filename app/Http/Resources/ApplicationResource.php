<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'student_id'        => $this->student_id,
            'internship_id'     => $this->internship_id,
            'cover_letter_text' => $this->cover_letter_text,
            'portfolio_url'     => $this->portfolio_url,
            'status'            => $this->status,
            'created_at'        => $this->created_at->toDateTimeString(),
            
            // Relationships
            'student'           => $this->whenLoaded('student'),
            'internship'        => new InternshipResource($this->whenLoaded('internship')),
            'documents'         => $this->whenLoaded('documents'),
        ];
    }
}
