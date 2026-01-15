<?php

namespace App\Models\Concerns;

trait HasTenantKey
{
    protected static function bootHasTenantKey(): void
    {
        // Tenant filtering disabled (single shared database)
    }
}
