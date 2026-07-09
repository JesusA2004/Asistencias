<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'business_name', 'rfc', 'status'])]
class Client extends Model
{
    use HasFactory, SoftDeletes;

    public function servicePoints(): HasMany
    {
        return $this->hasMany(ServicePoint::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
