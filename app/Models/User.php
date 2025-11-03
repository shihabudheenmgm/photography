<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'profile_image',
        'logo',
        'brand_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $today = now()->format('Ymd');

            $countToday = self::whereDate('created_at', now()->toDateString())->count();
            $index = str_pad($countToday + 1, 2, '0', STR_PAD_LEFT);

            $model->id = "{$today}{$index}";
        });
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class);
    }
}

