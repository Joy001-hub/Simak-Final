<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    use HasTenantKey;

    protected $fillable = [
        'tenant_key',
        'name',
        'location',
        'notes',
        'total_units',
        'sold_units',
    ];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }
}
