<?php

namespace Database\Seeders\Permissions;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Role;
use App\Services\ACLService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private ACLService $aclService;

    public function __construct(ACLService $aclService)
    {
        $this->aclService = $aclService;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define roles
        $userRole = $this->aclService->createRole(ROLE_ENUM::USER);
        $adminRole = $this->aclService->createRole(ROLE_ENUM::ADMIN);

        // Create scoped permissions
        $this->aclService->createScopePermissions('users', ['create', 'read', 'update', 'delete']);

        // Assign permissions to roles
        $this->aclService->assignScopePermissionsToRole($adminRole, 'users', ['create', 'read', 'update', 'delete']);
    }

    public function rollback()
    {
        $adminRole = Role::where('name', ROLE_ENUM::ADMIN)->first();
        $this->aclService->removeScopePermissionsFromRole($adminRole, 'users', ['create', 'read', 'update', 'delete']);
    }
}
