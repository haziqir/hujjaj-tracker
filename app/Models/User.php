<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function groupsLeader(): HasMany
    {
        return $this->hasMany(Group::class, 'leader_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'staff_id');
    }

    public function pilgrim(): HasOne
    {
        return $this->hasOne(Pilgrim::class);
    }

    public function hasPermission($permissionName): bool
    {
        return $this->roles()->whereHas('permissions', function($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class,'user_roles')->withPivot('user_id', 'role_id');
    }
}
