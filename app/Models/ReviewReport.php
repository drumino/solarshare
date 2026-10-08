<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    public const REASONS = [
        'spam' => 'Spam / publicité',
        'offensive' => 'Propos offensants',
        'fake' => 'Faux avis',
        'other' => 'Autre',
    ];

    public const STATUSES = ['open' => 'Ouvert', 'resolved' => 'Traité', 'dismissed' => 'Rejeté'];

    protected $fillable = ['review_id', 'user_id', 'reason', 'details', 'status'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
