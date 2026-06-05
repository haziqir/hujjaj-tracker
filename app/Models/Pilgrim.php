<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name','passport_no','gender','age','group_id','hotel_id','emergency_contact'])]
class Pilgrim extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function sosAlerts(): HasMany
    {
        return $this->hasMany(SosAlert::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
