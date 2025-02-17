<?php

namespace Database\Seeders;

use App\Enums\ROLE;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (env('APP_ENV') === 'prod') {
            $admin = User::firstOrCreate(
                ['email' => 'admin@cysc.fr'],
                ['password' => bcrypt('fnFPB3TzGWTBoLA')],
            );
            $admin->assignRole(ROLE::ADMIN);

            $user = User::firstOrCreate(
                ['email' => 'user@cysc.fr'],
                ['password' => bcrypt('nRapnRYRdxcE')],
            );
            $user->assignRole(ROLE::USER);
        } else {
            $admin = User::firstOrCreate(
                ['email' => 'admin@cysc.fr'],
                ['password' => bcrypt('admin')],
            );
            $admin->assignRole(ROLE::ADMIN);
            $user = User::firstOrCreate(
                ['email' => 'user@cysc.fr'],
                ['password' => bcrypt('user')],
            );
            $user->assignRole(ROLE::USER);
        }
    }
}
