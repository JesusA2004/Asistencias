<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['attendance_id', 'event_type', 'event_time', 'value', 'created_by', 'ip_address', 'user_agent', 'notes', 'created_at'])]
class AttendanceEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AttendancePhoto::class, 'attendance_event_id');
    }
}
