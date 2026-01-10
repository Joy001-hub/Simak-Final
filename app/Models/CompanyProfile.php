<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;
    use HasTenantKey;

    protected $fillable = [
        'tenant_key',
        'name',
        'npwp',
        'email',
        'phone',
        'address',
        'signer_name',
        'footer_note',
        'invoice_format',
        'receipt_format',
        'logo_path',
    ];
}
