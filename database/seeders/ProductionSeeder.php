<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    /**
     * Seed essential production data.
     *
     * This seeder creates:
     * - Roles and permissions (if not exists)
     * - Languages
     * - Workflow configuration
     * - Navigation menus
     * - Default post types
     * - Full category hierarchy
     * - Sample users (one per role)
     */
    public function run(): void
    {
        // Ensure core seeders have run
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            WorkflowSeeder::class,
            MenuSeeder::class,
            CategorySeeder::class,
        ]);

        $this->seedPostTypes();
        $this->seedUsers();
    }

    /**
     * Seed default post types configuration.
     */
    protected function seedPostTypes(): void
    {
        Setting::setPostTypes(Setting::getDefaultPostTypes());
        $this->command->info('Default post types seeded.');
    }

    /**
     * Seed sample users (one per role).
     */
    protected function seedUsers(): void
    {
        $password = Hash::make('password');

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'username' => 'admin', 'role' => 'Admin'],
            ['name' => 'Developer User', 'email' => 'developer@example.com', 'username' => 'developer', 'role' => 'Developer'],
            ['name' => 'Editor User', 'email' => 'editor@example.com', 'username' => 'editor', 'role' => 'Editor'],
            ['name' => 'Writer User', 'email' => 'writer@example.com', 'username' => 'writer', 'role' => 'Writer'],
            ['name' => 'Photographer User', 'email' => 'photographer@example.com', 'username' => 'photographer', 'role' => 'Photographer'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email_verified_at' => now(),
                    'password' => $password,
                ]
            );
            $user->assignRole($userData['role']);
        }

        $this->command->info('Sample users seeded (one per role).');
    }
}
