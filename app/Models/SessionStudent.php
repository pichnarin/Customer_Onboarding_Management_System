<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionStudent extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'session_id',
        'name',
        'phone_number',
        'profession',
    ];

    protected $casts = [
        'id' => 'string',
        'session_id' => 'string',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }
}
