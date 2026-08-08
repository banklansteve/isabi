<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkLogMedia extends Model
{
    public const DISK_CLOUDINARY = 'cloudinary';

    public const DISK_PUBLIC = 'public';

    protected $fillable = [
        'work_log_id',
        'disk',
        'path',
        'cdn_url',
        'original_name',
        'mime_type',
        'kind',
        'size',
        'sort_order',
    ];

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class);
    }

    public function url(): string
    {
        if (filled($this->cdn_url)) {
            return (string) $this->cdn_url;
        }

        if ($this->disk === self::DISK_CLOUDINARY) {
            return '';
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    public function isVideo(): bool
    {
        return $this->kind === 'video';
    }
}
