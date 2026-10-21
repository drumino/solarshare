<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $excerpt = \Illuminate\Support\Str::limit($this->review->comment, 80);
        return (new MailMessage)
            ->subject('Le propriétaire a répondu à votre avis')
            ->line('Le propriétaire a publié une réponse à votre avis.')
            ->line('Avis : '.$excerpt)
            ->action('Voir', route('front.home', absolute: true));
    }

    public function toArray($notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'excerpt' => \Illuminate\Support\Str::limit($this->review->comment, 80),
            'link' => route('front.home', absolute: false),
        ];
    }
}
