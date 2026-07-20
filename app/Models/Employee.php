<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_number', 'name', 'last_name', 'second_last_name', 'email', 'phone', 'status', 'client_id', 'service_point_id', 'shift_id', 'user_id'])]
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->name} {$this->last_name} {$this->second_last_name}"),
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendancePhotos(): HasMany
    {
        return $this->hasMany(AttendancePhoto::class);
    }
}
