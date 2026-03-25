<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternshipResource extends JsonResource
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
            'title'             => $this->title,
            'category'          => $this->category,
            'description'       => $this->description,
            'location'          => $this->location,
            'type'              => $this->type,
            'duration'          => $this->duration,
            'stipend'           => $this->stipend,
            'paid'              => (bool) $this->paid,
            'deadline'          => $this->deadline ? $this->deadline->toDateString() : null,
            'status'            => $this->status,
            'created_at'        => $this->created_at->toDateTimeString(),
            'target_faculty'    => $this->target_faculty,
            'target_department' => $this->target_department,
            
            // Relationships
            'recruiter'         => $this->whenLoaded('recruiter'),
            'company'           => $this->getCompanyInfo(),
            
            // Computed items for frontend
            'has_applied'        => $this->has_applied ?? false,
            'application_id'     => $this->application_id ?? null,
            'application_status' => $this->application_status ?? null,
            'is_saved'           => $this->is_saved ?? false,
        ];
    }

    protected function getCompanyInfo()
    {
        if ($this->recruiter && $this->recruiter->company) {
            $company = $this->recruiter->company;
            return [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'logo_url'     => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'website'      => $company->website,
            ];
        }

        if ($this->recruiter && $this->recruiter->company_name) {
            return [
                'company_name' => $this->recruiter->company_name,
            ];
        }

        return null;
    }
}
