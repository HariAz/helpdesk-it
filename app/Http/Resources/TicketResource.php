<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'ticket_number'  => $this->ticket_number,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => $this->status,
            'status_label'   => \App\Models\Ticket::STATUS_LABELS[$this->status] ?? $this->status,
            'priority'       => $this->priority,
            'is_escalated'   => $this->is_escalated,
            'sla_deadline'   => $this->sla_deadline?->toIso8601String(),
            'resolved_at'    => $this->resolved_at?->toIso8601String(),
            'closed_at'      => $this->closed_at?->toIso8601String(),
            'created_at'     => $this->created_at->toIso8601String(),
            'updated_at'     => $this->updated_at->toIso8601String(),
            'user'     => $this->whenLoaded('user',     fn() => ['id' => $this->user->id,     'name' => $this->user->name,     'email' => $this->user->email]),
            'assignee' => $this->whenLoaded('assignee', fn() => $this->assignee ? ['id' => $this->assignee->id, 'name' => $this->assignee->name, 'email' => $this->assignee->email] : null),
            'category' => $this->whenLoaded('category', fn() => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name] : null),
            'subcategory' => $this->whenLoaded('subcategory', fn() => $this->subcategory ? ['id' => $this->subcategory->id, 'name' => $this->subcategory->name] : null),
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
        ];
    }
}
