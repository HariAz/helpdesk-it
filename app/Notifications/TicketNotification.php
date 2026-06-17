<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public Ticket $ticket,
        public string $message,
        public string $icon = 'bi-ticket-detailed',
        public string $color = 'primary'
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => $this->type,
            'ticket_id'  => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_title' => $this->ticket->title,
            'message'    => $this->message,
            'icon'       => $this->icon,
            'color'      => $this->color,
            'url'        => route('tickets.show', $this->ticket),
        ];
    }
}
