<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    public const SENTIMENTS = ['positive' => 'Positif', 'neutral' => 'Neutre', 'negative' => 'Négatif'];

    protected $fillable = [
        'reservation_id', 'equipment_id', 'user_id', 'rating', 'comment',
        'sentiment', 'sentiment_score', 'is_visible', 'moderation_note',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sentiment_score' => 'float',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }
}
