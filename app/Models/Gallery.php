<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gallery extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'category', 'user_id', 'images', 'marked_images', 'videos', 'video_links', 'shareable_link', 'brand_color','cover_image','cover_image_position',
    ];

    protected $casts = [
        'images' => 'array',
        'marked_images' => 'array',
        'videos' => 'array',
        'video_links' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $userId = $model->user_id;

            // Count galleries for this user only
            $count = self::where('user_id', $userId)->count();

            $index = str_pad($count + 1, 3, '0', STR_PAD_LEFT); 
            $model->id = "{$userId}{$index}";
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

