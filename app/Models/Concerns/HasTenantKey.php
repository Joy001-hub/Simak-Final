<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait HasTenantKey
{
    protected static function bootHasTenantKey(): void
    {
        static::creating(function ($model) {
            if (!app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $tenantKey = $context->getTenantKey();
            if ($context->isCloud() && $tenantKey) {
                $model->tenant_key = $tenantKey;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (!app()->bound(TenantContext::class)) {
                return;
            }

            $context = app(TenantContext::class);
            $tenantKey = $context->getTenantKey();
            if ($context->isCloud()) {
                if ($tenantKey) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_key', $tenantKey);
                } else {
                    $builder->whereRaw('1=0');
                }
            }
        });
    }
}
