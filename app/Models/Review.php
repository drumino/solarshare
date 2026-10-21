<?php

namespace App\Models;

use App\Notifications\ReviewHiddenNotification;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Review extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(function (Review $review) {
            foreach ($review->photos as $photo) {
                Storage::disk('public')->delete($photo->path);
            }
        });

        static::saved(function (Review $review) {
            $cache = app(Repository::class);
            $cache->forget('equipment.'.$review->equipment_id.'_average_rating');
            $cache->forget('equipment.'.$review->equipment_id.'_criteria_averages');
        });

        static::deleted(function (Review $review) {
            $cache = app(Repository::class);
            $cache->forget('equipment.'.$review->equipment_id.'_average_rating');
            $cache->forget('equipment.'.$review->equipment_id.'_criteria_averages');
        });
    }

    public const SENTIMENTS = ['positive' => 'Positif', 'neutral' => 'Neutre', 'negative' => 'Négatif'];

    protected $fillable = [
        'reservation_id', 'equipment_id', 'user_id', 'rating', 'comment',
        'sentiment', 'sentiment_score', 'is_visible', 'moderation_note',
        'owner_reply', 'owner_reply_at', 'owner_reply_by', 'owner_reply_edited_at',
        'helpful_count', 'aspects', 'criteria_ratings', 'flags', 'edited_at',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sentiment_score' => 'float',
        'owner_reply_at' => 'datetime',
        'owner_reply_edited_at' => 'datetime',
        'helpful_count' => 'integer',
        'aspects' => 'array',
        'criteria_ratings' => 'array',
        'flags' => 'array',
        'edited_at' => 'datetime',
    ];

    public static function computeRating(array $criteria): int
    {
        $vals = array_values($criteria);

        return (int) round(array_sum($vals) / count($vals));
    }

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

    public function replyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_reply_by');
    }

    public function hide(?string $note = null): bool
    {
        if (! $this->is_visible) {
            return false;
        }
        $this->update([
            'is_visible' => false,
            'moderation_note' => $note ?? $this->moderation_note,
        ]);
        $this->refresh();
        if ($this->user) {
            $this->user->notify(new ReviewHiddenNotification($this, $note));
        }

        return true;
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReviewPhoto::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReviewRevision::class)->latest();
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function helpfulVoters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_votes', 'review_id', 'user_id')->withTimestamps();
    }
}
