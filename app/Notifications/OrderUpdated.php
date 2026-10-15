<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(public string $number, public string $status, public int $totalMinor) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Order {$this->number}: {$this->status}")
            ->line("Your order is now {$this->status}.")
            ->line('Order total: USD '.number_format($this->totalMinor / 100, 2, '.', ''));
    }
}
