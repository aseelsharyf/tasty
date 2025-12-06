<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Note: We don't use WithoutModelEvents because we need UUID generation

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            TaxonomySeeder::class,
        ]);

        // Create Admin users (2)
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@tasty.test',
            'username' => 'admin',
        ]);
        User::factory()->admin()->create([
            'name' => 'Sarah Admin',
            'email' => 'sarah.admin@tasty.test',
            'username' => 'sarah_admin',
        ]);

        // Create Developer user
        User::factory()->developer()->create([
            'name' => 'Dev User',
            'email' => 'dev@tasty.test',
            'username' => 'developer',
        ]);

        // Create Editor users (3)
        User::factory()->editor()->create([
            'name' => 'Editor User',
            'email' => 'editor@tasty.test',
            'username' => 'editor',
        ]);
        User::factory()->editor()->create([
            'name' => 'Maya Editor',
            'email' => 'maya.editor@tasty.test',
            'username' => 'maya_editor',
        ]);
        User::factory()->editor()->create([
            'name' => 'Ahmed Editor',
            'email' => 'ahmed.editor@tasty.test',
            'username' => 'ahmed_editor',
        ]);

        // Create Writer users (4)
        User::factory()->writer()->create([
            'name' => 'Writer One',
            'email' => 'writer1@tasty.test',
            'username' => 'writer1',
        ]);
        User::factory()->writer()->create([
            'name' => 'Fatima Writer',
            'email' => 'fatima.writer@tasty.test',
            'username' => 'fatima_writer',
        ]);
        User::factory()->writer()->create([
            'name' => 'Ali Writer',
            'email' => 'ali.writer@tasty.test',
            'username' => 'ali_writer',
        ]);
        User::factory()->writer()->create([
            'name' => 'Mariyam Writer',
            'email' => 'mariyam.writer@tasty.test',
            'username' => 'mariyam_writer',
        ]);

        // Create Photographer users (2)
        User::factory()->photographer()->create([
            'name' => 'Photographer User',
            'email' => 'photographer@tasty.test',
            'username' => 'photographer',
        ]);
        User::factory()->photographer()->create([
            'name' => 'Hassan Photo',
            'email' => 'hassan.photo@tasty.test',
            'username' => 'hassan_photo',
        ]);

        // Create regular users (no CMS access)
        User::factory()->regularUser()->count(5)->create();

        // Seed workflow configuration
        $this->call(WorkflowSeeder::class);

        // Seed comprehensive CMS workflow data
        $this->call(CmsWorkflowDataSeeder::class);

        // Seed user comments on published posts
        $this->call(CommentSeeder::class);
    }
}
