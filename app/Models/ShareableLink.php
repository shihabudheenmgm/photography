<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShareableLink extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'gallery_id',
        'token',
        'enable_download',
        'expires_at',
        'download_limit',
        'download_count',
        'restrictions'
    ];

    protected $casts = [
        'download_enabled_at' => 'datetime',
        'expires_at' => 'datetime',
        'restrictions' => 'array'
    ];

    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }

    public function isDownloadEnabled(): bool
    {
        return $this->download_enabled_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasReachedDownloadLimit(): bool
    {
        return $this->download_limit && $this->download_count >= $this->download_limit;
    }
}
