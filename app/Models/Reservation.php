<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    public const STATUSES = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'ongoing' => 'En cours',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    protected $fillable = ['equipment_id', 'user_id', 'start_date', 'end_date', 'total_price', 'status', 'notes'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_price' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /** Réservations qui bloquent le calendrier. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'ongoing']);
    }

    /** Réservations dont la période chevauche [start, end]. */
    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start);
    }

    public function getDaysAttribute(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_amount >= (float) $this->total_price;
    }
}
