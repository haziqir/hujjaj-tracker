<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['hotel_name','address','latitude','longitude'])]
class Hotel extends Model
{
    public function pilgrims(): HasMany
    {
        return $this->hasMany(Pilgrim::class);
    }
}
