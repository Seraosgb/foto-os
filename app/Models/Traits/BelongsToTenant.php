<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Gera UUID automaticamente antes de criar
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (empty($model->company_id) && session()->has('tenant_id')) {
                $model->company_id = session()->get('tenant_id');
            }
        });

        // Isola as consultas por Tenant (Multi-Tenant)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session()->has('tenant_id')) {
                $builder->where('company_id', session()->get('tenant_id'));
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
