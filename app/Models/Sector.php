<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sector extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'unit_id',
        'name',
        'normalized_name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * O setor pertence a uma unidade específica.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
