<?php

namespace App\Models;

use App\Models\Concerns\HasTenantKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    use HasTenantKey;

    public const STATUS_CANCELED_HAPUS = 'DIBATALKAN_HAPUS';
    public const STATUS_CANCELED_REFUND = 'DIBATALKAN_REFUND';
    public const STATUS_CANCELED_OPER_KREDIT = 'DIALIHKAN_OPER_KREDIT';

    protected $fillable = [
        'tenant_key',
        'lot_id',
        'buyer_id',
        'marketer_id',
        'booking_date',
        'payment_method',
        'price',
        'down_payment',
        'tenor_months',
        'due_day',
        'paid_amount',
        'outstanding_amount',
        'status',
        'refund_amount',
        'status_before_cancel',
        'parent_sale_id',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function marketer()
    {
        return $this->belongsTo(Marketer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
