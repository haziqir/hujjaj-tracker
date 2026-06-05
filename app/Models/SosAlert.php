<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pilgrim_id','latitude','longitude','status'])]
class SosAlert extends Model
{
    public function pilgrim(): BelongsTo
    {
        return $this->belongsTo(Pilgrim::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'alert_id');
    }
}
