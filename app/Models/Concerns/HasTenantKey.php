<?php

namespace App\Models\Concerns;

trait HasTenantKey
{
    protected static function bootHasTenantKey(): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        static::addGlobalScope('tenant', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_key', (string) auth()->id());
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_key)) {
                $model->tenant_key = (string) auth()->id();
            }
        });
    }
}
