<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'os_number',
        'unit_id',
        'status_id',
        'history',
        'technicians',
        'server_created_at',
        'finalized_at',
    ];

    protected $casts = [
        'server_created_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    /**
     * Relação com a Empresa / Tenant detentor do relatório
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relação com os Setores (Muitos para Muitos via report_sectors)
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'report_sectors');
    }

    /**
     * Relação com as Fotografias (Um para Muitos)
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sequence');
    }

    /**
     * Relação com a Unidade
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relação com o Status Dinâmico
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ReportStatus::class, 'status_id');
    }
}
