<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['pilgrim_id','latitude','longitude','status'])]
class SosAlert extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function pilgrim(): BelongsTo
    {
        return $this->belongsTo(Pilgrim::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'alert_id');
    }

    public function latestAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class, 'alert_id')->latestOfMany();
    }
}
