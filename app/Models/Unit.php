<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'normalized_name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Uma unidade pode ter vários setores.
     */
    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
    }
}
