<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        // Create Admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@tasty.test',
            'username' => 'admin',
        ]);

        // Create Developer user
        User::factory()->developer()->create([
            'name' => 'Dev User',
            'email' => 'dev@tasty.test',
            'username' => 'developer',
        ]);

        // Create Editor user
        User::factory()->editor()->create([
            'name' => 'Editor User',
            'email' => 'editor@tasty.test',
            'username' => 'editor',
        ]);

        // Create Writer users
        User::factory()->writer()->count(3)->create();

        // Create Photographer user
        User::factory()->photographer()->create([
            'name' => 'Photographer User',
            'email' => 'photographer@tasty.test',
            'username' => 'photographer',
        ]);

        // Create regular users (no CMS access)
        User::factory()->regularUser()->count(5)->create();
    }
}
