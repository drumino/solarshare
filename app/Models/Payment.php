<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['card' => 'Carte bancaire', 'cash' => 'Espèces à la remise', 'transfer' => 'Virement'];

    public const STATUSES = ['pending' => 'En attente', 'paid' => 'Payé', 'refunded' => 'Remboursé'];

    protected $fillable = ['reservation_id', 'amount', 'method', 'status', 'reference', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
