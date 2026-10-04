<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    public const CATEGORIES = [
        'hardware' => 'Panne matérielle',
        'battery' => 'Batterie / charge',
        'safety' => 'Sécurité',
        'damage' => 'Dommage physique',
        'other' => 'Autre',
    ];

    public const SEVERITIES = ['low' => 'Faible', 'medium' => 'Moyenne', 'high' => 'Élevée', 'critical' => 'Critique'];

    public const STATUSES = ['open' => 'Ouvert', 'in_progress' => 'En cours', 'resolved' => 'Résolu'];

    protected $fillable = [
        'equipment_id', 'reporter_id', 'reservation_id', 'title', 'description',
        'category', 'severity', 'status', 'ai_summary', 'ai_advice',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class);
    }
}
