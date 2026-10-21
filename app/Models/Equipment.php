<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'equipment';

    public const TYPES = [
        'solar_panel' => 'Panneau solaire portable',
        'battery' => 'Batterie / station d\'énergie',
        'wind' => 'Mini-éolienne',
        'inverter' => 'Onduleur / régulateur',
        'other' => 'Autre',
    ];

    public const CONDITIONS = [
        'neuf' => 'Neuf',
        'tres_bon' => 'Très bon état',
        'bon' => 'Bon état',
        'usage' => 'Usagé',
    ];

    public const STATUSES = [
        'pending' => 'En attente de validation',
        'available' => 'Disponible',
        'rented' => 'Loué',
        'maintenance' => 'En maintenance',
        'rejected' => 'Refusé',
    ];

    protected $fillable = [
        'category_id', 'owner_id', 'title', 'description', 'type', 'power_watts',
        'capacity_wh', 'price_per_day', 'deposit', 'condition', 'city', 'status', 'image_path',
        'reviews_count', 'reviews_avg', 'bayesian_score',
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'deposit' => 'decimal:2',
        'reviews_count' => 'integer',
        'reviews_avg' => 'decimal:2',
        'bayesian_score' => 'decimal:3',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->where('is_visible', true)->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    public function isTopRated(): bool
    {
        $minAvg = (float) config('solarshare.reviews.top_rated_min_avg', 4.5);
        $minCount = (int) config('solarshare.reviews.top_rated_min_count', 10);

        return (int) $this->reviews_count >= $minCount
            && (float) $this->reviews_avg >= $minAvg;
    }

    public function getIsTopRatedAttribute(): bool
    {
        return $this->isTopRated();
    }
}