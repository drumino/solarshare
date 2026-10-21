<?php

namespace App\Notifications;

use App\Models\ReviewReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class HighSeverityReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReviewReport $report) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $excerpt = $this->reviewExcerpt();

        return (new MailMessage)
            ->subject('Signalement critique')
            ->line('Un signalement de sévérité élevée a été créé.')
            ->line('Motif : '.($this->report->reason ?? '—'))
            ->line('Catégorie : '.($this->report->category ?? 'non classée'))
            ->line('Action suggérée : '.($this->report->suggested_action ?? 'aucune'))
            ->line('Avis : '.($excerpt !== '' ? $excerpt : 'Avis indisponible'))
            ->action('Voir le signalement', route('admin.review-reports.show', $this->report, absolute: true));
    }

    public function toArray($notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'reason' => $this->report->reason,
            'category' => $this->report->category,
            'suggested_action' => $this->report->suggested_action,
            'excerpt' => $this->reviewExcerpt(),
            'link' => route('admin.review-reports.show', $this->report, absolute: false),
        ];
    }

    /** Extrait de l'avis signalé : vide si l'avis est absent ou supprimé (soft delete). */
    private function reviewExcerpt(): string
    {
        $comment = $this->report->review?->comment;

        return $comment ? Str::limit($comment, 80) : '';
    }
}
