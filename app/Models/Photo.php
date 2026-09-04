<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory, HasUuids; // Usa a trait nativa para UUIDs, pois o tenant já é blindado via Report

    protected $fillable = [
        'report_id',
        'sequence',
        'original_path',
        'processed_path',
        'observation',
        'latitude',
        'longitude',
        'address',
        'captured_at_server',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'captured_at_server' => 'datetime',
    ];

    /**
     * A foto pertence a um relatório.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
