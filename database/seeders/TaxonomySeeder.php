<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    /**
     * Seed categories and tags for a food travel blog.
     */
    public function run(): void
    {
        $this->createCategories();
        $this->createTags();
    }

    private function createCategories(): void
    {
        $categories = [
            // Main categories with children
            [
                'name' => 'Feature',
                'slug' => 'feature',
                'description' => 'Featured stories and highlights',
                'children' => [
                    ['name' => 'Editor\'s Pick', 'slug' => 'editors-pick', 'description' => 'Hand-picked stories by our editors'],
                    ['name' => 'Trending', 'slug' => 'trending', 'description' => 'What\'s popular right now'],
                    ['name' => 'Spotlight', 'slug' => 'spotlight', 'description' => 'In-depth features on special topics'],
                ],
            ],
            [
                'name' => 'People',
                'slug' => 'people',
                'description' => 'Stories about chefs, food makers, and culinary personalities',
                'children' => [
                    ['name' => 'Chef Profiles', 'slug' => 'chef-profiles', 'description' => 'Meet the chefs behind the food'],
                    ['name' => 'Interviews', 'slug' => 'interviews', 'description' => 'Conversations with food personalities'],
                    ['name' => 'Local Heroes', 'slug' => 'local-heroes', 'description' => 'Community food champions'],
                    ['name' => 'Food Artisans', 'slug' => 'food-artisans', 'description' => 'Craftspeople behind specialty foods'],
                ],
            ],
            [
                'name' => 'Review',
                'slug' => 'review',
                'description' => 'Restaurant and food reviews',
                'children' => [
                    ['name' => 'Restaurant Reviews', 'slug' => 'restaurant-reviews', 'description' => 'In-depth restaurant evaluations'],
                    ['name' => 'Cafe & Coffee', 'slug' => 'cafe-coffee', 'description' => 'Coffee shops and cafes'],
                    ['name' => 'Street Food', 'slug' => 'street-food', 'description' => 'Street food vendors and stalls'],
                    ['name' => 'Fine Dining', 'slug' => 'fine-dining', 'description' => 'Upscale dining experiences'],
                    ['name' => 'Budget Eats', 'slug' => 'budget-eats', 'description' => 'Great food that won\'t break the bank'],
                ],
            ],
            [
                'name' => 'Recipe',
                'slug' => 'recipe',
                'description' => 'Recipes and cooking guides',
                'children' => [
                    ['name' => 'Quick & Easy', 'slug' => 'quick-easy', 'description' => 'Recipes under 30 minutes'],
                    ['name' => 'Traditional', 'slug' => 'traditional', 'description' => 'Classic and heritage recipes'],
                    ['name' => 'Fusion', 'slug' => 'fusion', 'description' => 'Creative cross-cultural dishes'],
                    ['name' => 'Desserts', 'slug' => 'desserts', 'description' => 'Sweet treats and baked goods'],
                    ['name' => 'Drinks & Beverages', 'slug' => 'drinks-beverages', 'description' => 'Refreshing drinks and cocktails'],
                    ['name' => 'Vegetarian', 'slug' => 'vegetarian', 'description' => 'Meat-free recipes'],
                    ['name' => 'Seafood', 'slug' => 'seafood', 'description' => 'Fish and seafood dishes'],
                ],
            ],
            [
                'name' => 'Pantry',
                'slug' => 'pantry',
                'description' => 'Ingredients, products, and kitchen essentials',
                'children' => [
                    ['name' => 'Ingredients', 'slug' => 'ingredients', 'description' => 'Spotlight on specific ingredients'],
                    ['name' => 'Product Reviews', 'slug' => 'product-reviews', 'description' => 'Kitchen tools and product reviews'],
                    ['name' => 'Spices & Seasonings', 'slug' => 'spices-seasonings', 'description' => 'Guide to spices and seasonings'],
                    ['name' => 'Local Produce', 'slug' => 'local-produce', 'description' => 'Seasonal and local ingredients'],
                ],
            ],
            [
                'name' => 'Update',
                'slug' => 'update',
                'description' => 'News and updates from the food world',
                'children' => [
                    ['name' => 'News', 'slug' => 'news', 'description' => 'Latest food industry news'],
                    ['name' => 'Events', 'slug' => 'events', 'description' => 'Food festivals and events'],
                    ['name' => 'Openings', 'slug' => 'openings', 'description' => 'New restaurant and cafe openings'],
                    ['name' => 'Closings', 'slug' => 'closings', 'description' => 'Restaurant closures and farewells'],
                ],
            ],
            // Additional root categories
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Food-focused travel guides and stories',
                'children' => [
                    ['name' => 'Destinations', 'slug' => 'destinations', 'description' => 'Food travel destinations'],
                    ['name' => 'Food Tours', 'slug' => 'food-tours', 'description' => 'Guided food tour experiences'],
                    ['name' => 'Hidden Gems', 'slug' => 'hidden-gems', 'description' => 'Off-the-beaten-path discoveries'],
                    ['name' => 'City Guides', 'slug' => 'city-guides', 'description' => 'Complete city food guides'],
                ],
            ],
            [
                'name' => 'Culture',
                'slug' => 'culture',
                'description' => 'Food culture and traditions',
                'children' => [
                    ['name' => 'History', 'slug' => 'history', 'description' => 'Food history and origins'],
                    ['name' => 'Traditions', 'slug' => 'traditions', 'description' => 'Cultural food traditions'],
                    ['name' => 'Festivals', 'slug' => 'festivals', 'description' => 'Food-related festivals and celebrations'],
                ],
            ],
        ];

        $order = 1;
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = Category::create([
                ...$categoryData,
                'order' => $order++,
            ]);

            $childOrder = 1;
            foreach ($children as $childData) {
                Category::create([
                    ...$childData,
                    'parent_id' => $parent->id,
                    'order' => $childOrder++,
                ]);
            }
        }
    }

    private function createTags(): void
    {
        $tags = [
            // Cuisine types
            'Maldivian',
            'Asian',
            'Indian',
            'Sri Lankan',
            'Thai',
            'Japanese',
            'Chinese',
            'Italian',
            'Mediterranean',
            'Middle Eastern',
            'American',
            'Mexican',
            'French',
            'Korean',
            'Vietnamese',

            // Dietary
            'Vegetarian',
            'Vegan',
            'Gluten-Free',
            'Halal',
            'Dairy-Free',
            'Keto',
            'Low-Carb',
            'Healthy',

            // Meal types
            'Breakfast',
            'Brunch',
            'Lunch',
            'Dinner',
            'Snacks',
            'Appetizers',
            'Main Course',
            'Dessert',
            'Drinks',

            // Cooking methods
            'Grilled',
            'Fried',
            'Baked',
            'Steamed',
            'Raw',
            'Slow-Cooked',
            'Smoked',
            'Fermented',

            // Occasions
            'Party Food',
            'Date Night',
            'Family Meal',
            'Quick Meal',
            'Weekend Cooking',
            'Holiday',
            'Ramadan',
            'Eid',

            // Experience
            'Budget-Friendly',
            'Luxury',
            'Casual',
            'Romantic',
            'Family-Friendly',
            'Solo Dining',
            'Group Dining',

            // Location-based
            'Male\'',
            'Hulhumale\'',
            'Addu',
            'Resorts',
            'Local Island',
            'Airport',

            // Content type
            'Video',
            'Photo Essay',
            'Long Read',
            'Quick Tip',
            'How-To',
            'Guide',
            'List',
            'Opinion',

            // Seasonal
            'Summer',
            'Monsoon',
            'Dry Season',
            'Seasonal',
            'Year-Round',

            // Special
            'Award-Winning',
            'Must-Try',
            'Classic',
            'New',
            'Trending',
            'Staff Pick',
            'Reader Favorite',
        ];

        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'slug' => \Str::slug($tagName),
            ]);
        }
    }
}
