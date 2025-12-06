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
        Setting::set('workflow.default', [
            'name' => 'Default Editorial Workflow',
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'color' => 'neutral', 'icon' => 'i-lucide-file-edit'],
                ['key' => 'review', 'label' => 'Editorial Review', 'color' => 'warning', 'icon' => 'i-lucide-eye'],
                ['key' => 'copydesk', 'label' => 'Copy Desk', 'color' => 'info', 'icon' => 'i-lucide-spell-check'],
                ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'icon' => 'i-lucide-check-circle'],
                ['key' => 'rejected', 'label' => 'Needs Revision', 'color' => 'error', 'icon' => 'i-lucide-alert-circle'],
                ['key' => 'published', 'label' => 'Published', 'color' => 'primary', 'icon' => 'i-lucide-globe'],
            ],
            'transitions' => [
                ['from' => 'draft', 'to' => 'review', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Submit for Review'],
                ['from' => 'review', 'to' => 'copydesk', 'roles' => ['Editor', 'Admin'], 'label' => 'Send to Copy Desk'],
                ['from' => 'review', 'to' => 'rejected', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                ['from' => 'copydesk', 'to' => 'approved', 'roles' => ['Editor', 'Admin'], 'label' => 'Approve'],
                ['from' => 'copydesk', 'to' => 'rejected', 'roles' => ['Editor', 'Admin'], 'label' => 'Request Revisions'],
                ['from' => 'rejected', 'to' => 'review', 'roles' => ['Writer', 'Editor', 'Admin'], 'label' => 'Resubmit'],
                ['from' => 'approved', 'to' => 'published', 'roles' => ['Editor', 'Admin'], 'label' => 'Publish'],
                ['from' => 'published', 'to' => 'draft', 'roles' => ['Editor', 'Admin'], 'label' => 'Unpublish'],
            ],
            'publish_roles' => ['Editor', 'Admin'],
        ], 'workflow');

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
