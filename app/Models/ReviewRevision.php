<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRevision extends Model
{
    protected $fillable = [
        'review_id', 'old_rating', 'old_comment', 'old_sentiment',
        'old_criteria_ratings', 'edited_at',
    ];

    protected $casts = [
        'old_criteria_ratings' => 'array',
        'edited_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
