<?php

namespace App\Models;

class Permission extends BaseModel
{
    public static $cacheKey = 'permissions';

    protected $fillable = [
        'name',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_permissions');
    }
}
