<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $author = User::first();

        // Home page
        Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Welcome to Tasty',
                'content' => $this->getHomeContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Tasty - Maldivian Food & Culture',
                'meta_description' => 'Celebrating the flavors, stories, and people behind Maldivian food culture.',
                'published_at' => now(),
            ]
        );

        // About page
        Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'About Tasty',
                'content' => $this->getAboutContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'About Us - Tasty',
                'meta_description' => 'Learn about Tasty and our mission to celebrate Maldivian food culture.',
                'published_at' => now(),
            ]
        );

        // Contact page
        Page::updateOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'Contact Us',
                'content' => $this->getContactContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Contact - Tasty',
                'meta_description' => 'Get in touch with the Tasty team.',
                'published_at' => now(),
            ]
        );

        // Editorial Policy
        Page::updateOrCreate(
            ['slug' => 'editorial-policy'],
            [
                'title' => 'Editorial Policy',
                'content' => $this->getEditorialPolicyContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Editorial Policy - Tasty',
                'meta_description' => 'Our commitment to honest, thoughtful food journalism.',
                'published_at' => now(),
            ]
        );

        // Work With Us
        Page::updateOrCreate(
            ['slug' => 'work-with-us'],
            [
                'title' => 'Work With Us',
                'content' => $this->getWorkWithUsContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Careers - Tasty',
                'meta_description' => 'Join our team of food enthusiasts, writers, and creators.',
                'published_at' => now(),
            ]
        );

        // Submit Story
        Page::updateOrCreate(
            ['slug' => 'submit-story'],
            [
                'title' => 'Submit a Story',
                'content' => $this->getSubmitStoryContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Submit a Story - Tasty',
                'meta_description' => 'Got a food story worth telling? We want to hear it.',
                'published_at' => now(),
            ]
        );

        // Advertise
        Page::updateOrCreate(
            ['slug' => 'advertise'],
            [
                'title' => 'Advertise With Us',
                'content' => $this->getAdvertiseContent(),
                'layout' => 'full-width',
                'status' => Page::STATUS_PUBLISHED,
                'is_blade' => true,
                'author_id' => $author?->id,
                'meta_title' => 'Advertise - Tasty',
                'meta_description' => 'Connect your brand with the Maldives\' most engaged food audience.',
                'published_at' => now(),
            ]
        );
    }

    private function getHomeContent(): string
    {
        return file_get_contents(resource_path('views/home.blade.php'));
    }

    private function getAboutContent(): string
    {
        return file_get_contents(resource_path('views/pages/about.blade.php'));
    }

    private function getContactContent(): string
    {
        return file_get_contents(resource_path('views/pages/contact.blade.php'));
    }

    private function getEditorialPolicyContent(): string
    {
        return file_get_contents(resource_path('views/pages/editorial-policy.blade.php'));
    }

    private function getWorkWithUsContent(): string
    {
        return file_get_contents(resource_path('views/pages/work-with-us.blade.php'));
    }

    private function getSubmitStoryContent(): string
    {
        return file_get_contents(resource_path('views/pages/submit-story.blade.php'));
    }

    private function getAdvertiseContent(): string
    {
        return file_get_contents(resource_path('views/pages/advertise.blade.php'));
    }
}
