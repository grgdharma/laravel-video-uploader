<?php

namespace Dharma\VideoUploader\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    
    protected $table = 'videos';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'original_path',
        'processed_path',
        'thumbnail_path',
        'disk',
        'mime_type',
        'size',
        'duration',
        'width',
        'height',
        'status',
        'progress',
        'error_message',
    ];

    protected $casts = [
        'size' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'progress' => 'integer',
    ];
}