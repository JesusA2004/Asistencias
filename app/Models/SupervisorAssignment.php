<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supervisor_user_id', 'client_id', 'service_point_id'])]
class SupervisorAssignment extends Model
{
    use HasFactory;

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
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
