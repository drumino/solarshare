<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    public const REASONS = [
        'scheduled_maintenance' => 'Entretien programmé',
        'repair' => 'Réparation',
        'inspection' => 'Inspection de sécurité',
        'other' => 'Indisponibilité exceptionnelle',
    ];

    public const STATUSES = [
        'scheduled' => 'Planifiée',
        'in_progress' => 'En cours',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    protected $fillable = [
        'equipment_id', 'reported_by', 'title', 'reason', 'description', 'starts_on', 'ends_on',
        'status', 'cost', 'resolution_notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'cost' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /** Records that make an item unavailable on the specified date. */
    public function scopeBlockingOn(Builder $query, $date): Builder
    {
        return $query->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date);
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
