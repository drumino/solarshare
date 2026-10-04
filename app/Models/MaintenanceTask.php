<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTask extends Model
{
    public const STATUSES = ['planned' => 'Planifiée', 'in_progress' => 'En cours', 'done' => 'Terminée'];

    protected $fillable = ['incident_id', 'technician_id', 'title', 'planned_at', 'completed_at', 'cost', 'status', 'notes'];

    protected $casts = [
        'planned_at' => 'date',
        'completed_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
