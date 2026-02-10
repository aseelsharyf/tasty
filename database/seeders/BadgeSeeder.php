<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => ['en' => 'Verified'],
                'slug' => 'verified',
                'icon' => 'i-lucide-badge-check',
                'color' => 'success',
                'description' => ['en' => 'Verified user account.'],
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => ['en' => 'Staff'],
                'slug' => 'staff',
                'icon' => 'i-lucide-shield',
                'color' => 'primary',
                'description' => ['en' => 'Staff member of the team.'],
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => ['en' => 'Featured Chef'],
                'slug' => 'featured-chef',
                'icon' => 'i-lucide-chef-hat',
                'color' => 'warning',
                'description' => ['en' => 'Featured recipe contributor.'],
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate(
                ['slug' => $badge['slug']],
                $badge,
            );
        }
    }
}
