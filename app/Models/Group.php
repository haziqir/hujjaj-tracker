<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_name','group_code','leader_id','group_latitude','group_longitude','group_location_recorded_at'])]
class Group extends Model
{
    protected function casts(): array
    {
        return [
            'group_latitude' => 'decimal:8',
            'group_longitude' => 'decimal:8',
            'group_location_recorded_at' => 'datetime',
        ];
    }

    public function pilgrims(): HasMany
    {
        return $this->hasMany(Pilgrim::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
}
