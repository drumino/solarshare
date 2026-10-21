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

    public const STATUSES = ['pending' => 'En attente', 'reviewed' => 'Traité', 'dismissed' => 'Rejeté'];

    public const SEVERITIES = ['low' => 'Faible', 'medium' => 'Moyen', 'high' => 'Élevé'];

    public const CATEGORIES = ['insulte', 'arnaque', 'spam', 'donnees_personnelles', 'harcelement', 'autre'];

    public const ACTIONS = ['masquer', 'ignorer', 'avertir'];

    protected $fillable = [
        'review_id', 'user_id', 'reason', 'details', 'status', 'severity',
        'category', 'suggested_action', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->reporter();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
