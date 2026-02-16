<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendee extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'client_contact_id',
        'attendance_status',
        'attended_at',
        'notes',
    ];

    protected $casts = [
        'id'                => 'string',
        'session_id'        => 'string',
        'client_contact_id' => 'string',
        'attended_at'       => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function clientContact(): BelongsTo
    {
        return $this->belongsTo(ClientContact::class);
    }
}
