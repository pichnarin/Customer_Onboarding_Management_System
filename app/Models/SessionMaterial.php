<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMaterial extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'media_id',
        'material_type',
        'description',
        'uploaded_at',
    ];

    protected $casts = [
        'id'          => 'string',
        'session_id'  => 'string',
        'media_id'    => 'string',
        'uploaded_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
