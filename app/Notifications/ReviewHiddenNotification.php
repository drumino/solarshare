<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewHiddenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review, public ?string $note = null) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $excerpt = \Illuminate\Support\Str::limit($this->review->comment, 80);
        return (new MailMessage)
            ->subject('Votre avis a été masqué')
            ->line('Votre avis a été masqué par la modération.')
            ->line('Motif : '.($this->note ?? $this->review->moderation_note))
            ->line('Avis : '.$excerpt);
    }

    public function toArray($notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'note' => $this->note ?? $this->review->moderation_note,
            'excerpt' => \Illuminate\Support\Str::limit($this->review->comment, 80),
            'link' => route('front.home', absolute: false),
        ];
    }
}
