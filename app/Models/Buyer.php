<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;
    use HasTenantKey;

    protected $fillable = [
        'tenant_key',
        'name',
        'phone',
        'email',
        'address',
    ];
}
