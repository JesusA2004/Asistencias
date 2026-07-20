<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'attendance_id', 'attendance_event_id', 'employee_id', 'captured_by_user_id',
    'client_id', 'service_point_id', 'photo_path', 'thumbnail_path',
    'capture_type', 'capture_origin', 'captured_at', 'server_time', 'device_time',
    'ip_address', 'user_agent', 'latitude', 'longitude', 'metadata',
])]
class AttendancePhoto extends Model
{
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'server_time' => 'datetime',
            'device_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AttendanceEvent::class, 'attendance_event_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }
}
