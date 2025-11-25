<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusUpdated extends Notification
{
    use Queueable;

    public $reservation;

    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reservation Status Updated')
            ->line('Your reservation status has been updated.')
            ->line('Car: ' . $this->reservation->car->brand . ' ' . $this->reservation->car->model)
            ->line('Status: ' . $this->reservation->status)
            ->line('Thank you for using our service!');
    }
}
