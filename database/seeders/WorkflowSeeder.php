<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    /**
     * Seed the default workflow configurations.
     */
    public function run(): void
    {
        // Default Editorial Workflow - comprehensive for articles
        Setting::set('workflow.default', Setting::getDefaultWorkflow(), 'workflow');

        // Simpler workflow for recipes (fast-track)
        Setting::set('workflow.post_type.recipe', [
            'name' => 'Recipe Fast-Track',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'color' => 'neutral', 'icon' => 'i-lucide-file-edit'],
                ['key' => 'review', 'label' => 'Review', 'color' => 'warning', 'icon' => 'i-lucide-eye'],
                ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'icon' => 'i-lucide-check-circle'],
                ['key' => 'published', 'label' => 'Published', 'color' => 'primary', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                ['from' => 'draft', 'to' => 'review', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Submit'],
                ['from' => 'review', 'to' => 'approved', 'roles' => ['Editor', 'Admin'], 'label' => 'Approve'],
                ['from' => 'review', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Changes'],
                ['from' => 'approved', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'published', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
            ],
            'publish_roles' => ['Editor', 'Admin'],
        ], 'workflow');

        $this->command->info('Workflow configurations seeded successfully.');
    }
}
