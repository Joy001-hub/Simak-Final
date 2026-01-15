<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'sejoli_user_id',
        'sejoli_product_id',
        'subscription_status',
        'subscription_end_date',
        'last_subscription_check_at',
        'password_created_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'subscription_end_date' => 'datetime',
            'last_subscription_check_at' => 'datetime',
            'password_created_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
