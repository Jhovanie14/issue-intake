<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'priority'         => $this->priority->value,
            'category'         => $this->category,
            'status'           => $this->status->value,
            'summary'          => $this->summary,
            'suggested_action' => $this->suggested_action,
            'summary_source'   => $this->summary_source,
            'due_at'           => $this->due_at?->toIso8601String(),
            'escalated'        => (bool) $this->escalated,
            'escalated_at'     => $this->escalated_at?->toIso8601String(),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
