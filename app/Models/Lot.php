<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;
    use HasTenantKey;

    protected $fillable = [
        'tenant_key',
        'project_id',
        'block_number',
        'area',
        'base_price',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }
}
