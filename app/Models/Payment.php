<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    use HasTenantKey;

    protected $fillable = [
        'tenant_key',
        'sale_id',
        'due_date',
        'amount',
        'status',
        'note',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
