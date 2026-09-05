<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'retained_until',
        'is_archived',
    ];

    protected $casts = [
        'server_created_at' => 'datetime',
        'finalized_at' => 'datetime',
        'retained_until' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function getDraftExpirationDateAttribute(): Carbon
    {
        if ($this->retained_until) {
            return $this->retained_until;
        }
        return ($this->server_created_at ?? $this->created_at)->copy()->addDays(30);
    }

    public function getDaysUntilDraftPurgeAttribute(): int
    {
        return (int) ceil(now()->diffInDays($this->draft_expiration_date, false));
    }

    public function getIsDraftExpiringSoonAttribute(): bool
    {
        if ($this->status?->slug === 'finalizado' || $this->is_archived) {
            return false;
        }
        $days = $this->days_until_draft_purge;
        return $days >= 0 && $days <= 2;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'report_sectors');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sequence');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ReportStatus::class, 'status_id');
    }
}
