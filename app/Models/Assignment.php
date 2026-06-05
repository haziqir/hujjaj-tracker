<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['alert_id','staff_id','assigned_at','completed_at'])]
class Assignment extends Model
{
    public function alert(): BelongsTo
    {
        return $this->belongsTo(SosAlert::class, 'alert_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
