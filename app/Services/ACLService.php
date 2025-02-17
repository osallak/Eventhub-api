<?php

namespace App\Services;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Permission;
use App\Models\Role;

class ACLService
{
    protected $driver;

    protected $browser;

    public function __construct() {}

    public function createRole(ROLE_ENUM $roleEnum): Role
    {
        $role = Role::firstOrCreate(['name' => $roleEnum->value]);

        return $role;
    }

    public function createScopePermissions(string $scope, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $scope.'.'.$permission]);
        }
    }

    public function assignScopePermissionsToRole(Role $role, string $scope, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $permissionName = $scope.'.'.$permission;

            if (! $role->hasPermission($permissionName)) {
                $role->givePermission($permissionName);
            }
        }
    }

    public function removeScopePermissionsFromRole(Role $role, string $scope, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $permissionName = $scope.'.'.$permission;

            if ($role->hasPermission($permissionName)) {
                $role->removePermission($permissionName);
            }
        }
    }

    public function syncScopePermissionsToRole(Role $role, string $scope, array $permissions): void
    {
        $permissionNames = [];
        foreach ($permissions as $permission) {
            $permissionNames[] = $scope.'.'.$permission;
        }
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        $role->syncPermissions($permissions);
    }
}
