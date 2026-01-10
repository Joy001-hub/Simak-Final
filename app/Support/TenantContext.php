<?php

namespace App\Support;

class TenantContext
{
    private ?string $tenantKey = null;
    private string $mode = 'local';
    private bool $readOnly = false;
    private ?string $readOnlyReason = null;

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function isCloud(): bool
    {
        return $this->mode === 'cloud';
    }

    public function setTenantKey(?string $tenantKey): void
    {
        $this->tenantKey = $tenantKey;
    }

    public function getTenantKey(): ?string
    {
        return $this->tenantKey;
    }

    public function setReadOnly(bool $readOnly, ?string $reason = null): void
    {
        $this->readOnly = $readOnly;
        $this->readOnlyReason = $reason;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function getReadOnlyReason(): ?string
    {
        return $this->readOnlyReason;
    }
}
