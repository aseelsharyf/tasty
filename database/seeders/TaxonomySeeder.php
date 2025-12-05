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
                'name' => ['en' => 'Feature', 'dv' => 'ފީޗަރ'],
                'slug' => 'feature',
                'description' => ['en' => 'Featured stories and highlights', 'dv' => 'ފީޗަރ ވާހަކަތައް'],
                'children' => [
                    ['name' => ['en' => 'Editor\'s Pick', 'dv' => 'އެޑިޓަރުގެ ޗޮއިސް'], 'slug' => 'editors-pick', 'description' => ['en' => 'Hand-picked stories by our editors']],
                    ['name' => ['en' => 'Trending', 'dv' => 'ޓްރެންޑިންގ'], 'slug' => 'trending', 'description' => ['en' => 'What\'s popular right now']],
                    ['name' => ['en' => 'Spotlight', 'dv' => 'ސްޕޮޓްލައިޓް'], 'slug' => 'spotlight', 'description' => ['en' => 'In-depth features on special topics']],
                ],
            ],
            [
                'name' => ['en' => 'People', 'dv' => 'މީހުން'],
                'slug' => 'people',
                'description' => ['en' => 'Stories about chefs, food makers, and culinary personalities', 'dv' => 'ޝެފުންނާއި ކެއްކުމުގެ ފަންނުވެރިންގެ ވާހަކަ'],
                'children' => [
                    ['name' => ['en' => 'Chef Profiles', 'dv' => 'ޝެފް ޕްރޮފައިލް'], 'slug' => 'chef-profiles', 'description' => ['en' => 'Meet the chefs behind the food']],
                    ['name' => ['en' => 'Interviews', 'dv' => 'އިންޓަވިއު'], 'slug' => 'interviews', 'description' => ['en' => 'Conversations with food personalities']],
                    ['name' => ['en' => 'Local Heroes', 'dv' => 'ލޯކަލް ހީރޯސް'], 'slug' => 'local-heroes', 'description' => ['en' => 'Community food champions']],
                    ['name' => ['en' => 'Food Artisans', 'dv' => 'ކާނާގެ ފަންނުވެރިން'], 'slug' => 'food-artisans', 'description' => ['en' => 'Craftspeople behind specialty foods']],
                ],
            ],
            [
                'name' => ['en' => 'Review', 'dv' => 'ރިވިއު'],
                'slug' => 'review',
                'description' => ['en' => 'Restaurant and food reviews', 'dv' => 'ރެސްޓޯރެންޓް އަދި ކާނާގެ ރިވިއު'],
                'children' => [
                    ['name' => ['en' => 'Restaurant Reviews', 'dv' => 'ރެސްޓޯރެންޓް ރިވިއު'], 'slug' => 'restaurant-reviews', 'description' => ['en' => 'In-depth restaurant evaluations']],
                    ['name' => ['en' => 'Cafe & Coffee', 'dv' => 'ކެފੇ އެންޑް ކੋਫੀ'], 'slug' => 'cafe-coffee', 'description' => ['en' => 'Coffee shops and cafes']],
                    ['name' => ['en' => 'Street Food', 'dv' => 'ސްޓްރީޓް ފުޑް'], 'slug' => 'street-food', 'description' => ['en' => 'Street food vendors and stalls']],
                    ['name' => ['en' => 'Fine Dining', 'dv' => 'ފައިން ޑައިނިންގ'], 'slug' => 'fine-dining', 'description' => ['en' => 'Upscale dining experiences']],
                    ['name' => ['en' => 'Budget Eats', 'dv' => 'ބަޖެޓް އީޓްސް'], 'slug' => 'budget-eats', 'description' => ['en' => 'Great food that won\'t break the bank']],
                ],
            ],
            [
                'name' => ['en' => 'Recipe', 'dv' => 'ރެސިޕީ'],
                'slug' => 'recipe',
                'description' => ['en' => 'Recipes and cooking guides', 'dv' => 'ރެސިޕީ އަދި ކެއްކުމުގެ ގައިޑް'],
                'children' => [
                    ['name' => ['en' => 'Quick & Easy', 'dv' => 'އަވަސް އަދި ފަސޭހަ'], 'slug' => 'quick-easy', 'description' => ['en' => 'Recipes under 30 minutes']],
                    ['name' => ['en' => 'Traditional', 'dv' => 'ސަގާފީ'], 'slug' => 'traditional', 'description' => ['en' => 'Classic and heritage recipes']],
                    ['name' => ['en' => 'Fusion', 'dv' => 'ފިއުޝަން'], 'slug' => 'fusion', 'description' => ['en' => 'Creative cross-cultural dishes']],
                    ['name' => ['en' => 'Desserts', 'dv' => 'ފޮނި ކާނާ'], 'slug' => 'desserts', 'description' => ['en' => 'Sweet treats and baked goods']],
                    ['name' => ['en' => 'Drinks & Beverages', 'dv' => 'ބުއިންތައް'], 'slug' => 'drinks-beverages', 'description' => ['en' => 'Refreshing drinks and cocktails']],
                    ['name' => ['en' => 'Vegetarian', 'dv' => 'ވެޖިޓేરિয়ން'], 'slug' => 'vegetarian', 'description' => ['en' => 'Meat-free recipes']],
                    ['name' => ['en' => 'Seafood', 'dv' => 'ސީފުޑް'], 'slug' => 'seafood', 'description' => ['en' => 'Fish and seafood dishes']],
                ],
            ],
            [
                'name' => ['en' => 'Pantry', 'dv' => 'ބަދިގެ'],
                'slug' => 'pantry',
                'description' => ['en' => 'Ingredients, products, and kitchen essentials', 'dv' => 'ބާވަތްތަކާއި ބަދިގޭ ސާމާނު'],
                'children' => [
                    ['name' => ['en' => 'Ingredients', 'dv' => 'ބާވަތްތައް'], 'slug' => 'ingredients', 'description' => ['en' => 'Spotlight on specific ingredients']],
                    ['name' => ['en' => 'Product Reviews', 'dv' => 'ޕްރޮडक্ট ރިވިއު'], 'slug' => 'product-reviews', 'description' => ['en' => 'Kitchen tools and product reviews']],
                    ['name' => ['en' => 'Spices & Seasonings', 'dv' => 'ހަވާދާއި ރަހަ'], 'slug' => 'spices-seasonings', 'description' => ['en' => 'Guide to spices and seasonings']],
                    ['name' => ['en' => 'Local Produce', 'dv' => 'ލޯކަލް ޕްރޮޑక્ટ'], 'slug' => 'local-produce', 'description' => ['en' => 'Seasonal and local ingredients']],
                ],
            ],
            [
                'name' => ['en' => 'Update', 'dv' => 'އަޕްडेट'],
                'slug' => 'update',
                'description' => ['en' => 'News and updates from the food world', 'dv' => 'ކާނާގެ ދުނިޔޭގެ ޚަބަރު'],
                'children' => [
                    ['name' => ['en' => 'News', 'dv' => 'ޚަބަރު'], 'slug' => 'news', 'description' => ['en' => 'Latest food industry news']],
                    ['name' => ['en' => 'Events', 'dv' => 'އިވެންޓްސް'], 'slug' => 'events', 'description' => ['en' => 'Food festivals and events']],
                    ['name' => ['en' => 'Openings', 'dv' => 'އާ ތަންތަން'], 'slug' => 'openings', 'description' => ['en' => 'New restaurant and cafe openings']],
                    ['name' => ['en' => 'Closings', 'dv' => 'ބަންދުވުން'], 'slug' => 'closings', 'description' => ['en' => 'Restaurant closures and farewells']],
                ],
            ],
            // Additional root categories
            [
                'name' => ['en' => 'Travel', 'dv' => 'ދަތުރު'],
                'slug' => 'travel',
                'description' => ['en' => 'Food-focused travel guides and stories', 'dv' => 'ކާނާއާ ގުޅޭ ދަތުރު ވާހަކަ'],
                'children' => [
                    ['name' => ['en' => 'Destinations', 'dv' => 'މަންزިލްތައް'], 'slug' => 'destinations', 'description' => ['en' => 'Food travel destinations']],
                    ['name' => ['en' => 'Food Tours', 'dv' => 'ފުޑް ޓުއާސް'], 'slug' => 'food-tours', 'description' => ['en' => 'Guided food tour experiences']],
                    ['name' => ['en' => 'Hidden Gems', 'dv' => 'ފޮރުވިފައިވާ ތަންތަން'], 'slug' => 'hidden-gems', 'description' => ['en' => 'Off-the-beaten-path discoveries']],
                    ['name' => ['en' => 'City Guides', 'dv' => 'ސिटी ގައイڈ'], 'slug' => 'city-guides', 'description' => ['en' => 'Complete city food guides']],
                ],
            ],
            [
                'name' => ['en' => 'Culture', 'dv' => 'ސަގާފަތް'],
                'slug' => 'culture',
                'description' => ['en' => 'Food culture and traditions', 'dv' => 'ކާނާގެ ސަގާފަތާއި އާދަކާދަ'],
                'children' => [
                    ['name' => ['en' => 'History', 'dv' => 'ތާރީޚް'], 'slug' => 'history', 'description' => ['en' => 'Food history and origins']],
                    ['name' => ['en' => 'Traditions', 'dv' => 'އާދަކާދަ'], 'slug' => 'traditions', 'description' => ['en' => 'Cultural food traditions']],
                    ['name' => ['en' => 'Festivals', 'dv' => 'ފެސްٹিવલ'], 'slug' => 'festivals', 'description' => ['en' => 'Food-related festivals and celebrations']],
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
            ['en' => 'Maldivian', 'dv' => 'ދިވެހި'],
            ['en' => 'Asian', 'dv' => 'އޭޝިއަން'],
            ['en' => 'Indian', 'dv' => 'އިންडियન'],
            ['en' => 'Sri Lankan', 'dv' => 'ލަންކާ'],
            ['en' => 'Thai', 'dv' => 'ތައި'],
            ['en' => 'Japanese', 'dv' => 'ޖަޕާން'],
            ['en' => 'Chinese', 'dv' => 'ޗައިނީސް'],
            ['en' => 'Italian', 'dv' => 'އިޓަލީ'],
            ['en' => 'Mediterranean', 'dv' => 'މެڈิٹެރੇنియன'],
            ['en' => 'Middle Eastern', 'dv' => 'މެދުއިރުމަތި'],
            ['en' => 'American', 'dv' => 'އެމެरিކާ'],
            ['en' => 'Mexican', 'dv' => 'މެކ्ਸికੋ'],
            ['en' => 'French', 'dv' => 'ފްރެންچ'],
            ['en' => 'Korean', 'dv' => 'ކੋરियন'],
            ['en' => 'Vietnamese', 'dv' => 'ވியެٹनाម'],

            // Dietary
            ['en' => 'Vegetarian', 'dv' => 'ވެޖިٹేరियन'],
            ['en' => 'Vegan', 'dv' => 'ވީگن'],
            ['en' => 'Gluten-Free', 'dv' => 'ގްލూٹن ފްރީ'],
            ['en' => 'Halal', 'dv' => 'ހަލާލް'],
            ['en' => 'Dairy-Free', 'dv' => 'ڈیری ފްރީ'],
            ['en' => 'Keto', 'dv' => 'ކީٹੋ'],
            ['en' => 'Low-Carb', 'dv' => 'ލੋ ކާބް'],
            ['en' => 'Healthy', 'dv' => 'ސިއްحی'],

            // Meal types
            ['en' => 'Breakfast', 'dv' => 'ހެނދުނުގެ ނާސްتާ'],
            ['en' => 'Brunch', 'dv' => 'ބްރަންچ'],
            ['en' => 'Lunch', 'dv' => 'މެންدوپهر'],
            ['en' => 'Dinner', 'dv' => 'ރޭگانގެ ކެއުން'],
            ['en' => 'Snacks', 'dv' => 'ސްنیکްސް'],
            ['en' => 'Appetizers', 'dv' => 'އެപیٹائزર'],
            ['en' => 'Main Course', 'dv' => 'މެއین ކੋރސް'],
            ['en' => 'Dessert', 'dv' => 'ފޮނި ކާނާ'],
            ['en' => 'Drinks', 'dv' => 'ބުއިންތައް'],

            // Cooking methods
            ['en' => 'Grilled', 'dv' => 'ފިހެފައި'],
            ['en' => 'Fried', 'dv' => 'ތެލުލައިފައި'],
            ['en' => 'Baked', 'dv' => 'ފިهެފައި'],
            ['en' => 'Steamed', 'dv' => 'އާވީގައި'],
            ['en' => 'Raw', 'dv' => 'ރੋ'],
            ['en' => 'Slow-Cooked', 'dv' => 'ސްلو ކੁ کڈ'],
            ['en' => 'Smoked', 'dv' => 'ދުން އެޅި'],
            ['en' => 'Fermented', 'dv' => 'ފާ ކޮށްفای'],

            // Occasions
            ['en' => 'Party Food', 'dv' => 'ޕާٹی ކާنާ'],
            ['en' => 'Date Night', 'dv' => 'ڈیٹ ނައิٹ'],
            ['en' => 'Family Meal', 'dv' => 'އާއިلے ކެއުން'],
            ['en' => 'Quick Meal', 'dv' => 'އަވަސް ކެއުން'],
            ['en' => 'Weekend Cooking', 'dv' => 'ހަفته ބަންدުގެ ކެއްކުން'],
            ['en' => 'Holiday', 'dv' => 'ޗުއްٹی'],
            ['en' => 'Ramadan', 'dv' => 'ރަމަضان'],
            ['en' => 'Eid', 'dv' => 'ޢީދު'],

            // Experience
            ['en' => 'Budget-Friendly', 'dv' => 'ބަジیٹް فرینڈلی'],
            ['en' => 'Luxury', 'dv' => 'ލަގްژری'],
            ['en' => 'Casual', 'dv' => 'ކੇجوअल'],
            ['en' => 'Romantic', 'dv' => 'ރੋمینٹک'],
            ['en' => 'Family-Friendly', 'dv' => 'އާއިلے فرینڈلی'],
            ['en' => 'Solo Dining', 'dv' => 'ސੋلो ڈائننگ'],
            ['en' => 'Group Dining', 'dv' => 'ގްރੂپް ڈائننگ'],

            // Location-based
            ['en' => 'Male\'', 'dv' => 'މާލެ'],
            ['en' => 'Hulhumale\'', 'dv' => 'ހުޅުمالے'],
            ['en' => 'Addu', 'dv' => 'އައްڈو'],
            ['en' => 'Resorts', 'dv' => 'ރިزورٹ'],
            ['en' => 'Local Island', 'dv' => 'ރަށް'],
            ['en' => 'Airport', 'dv' => 'އެއާپورٹ'],

            // Content type
            ['en' => 'Video', 'dv' => 'ویڈیو'],
            ['en' => 'Photo Essay', 'dv' => 'ފੋٹੋ އެسے'],
            ['en' => 'Long Read', 'dv' => 'ލੋންگ ریڈ'],
            ['en' => 'Quick Tip', 'dv' => 'ކوئک ٹپ'],
            ['en' => 'How-To', 'dv' => 'ހައު-ٹੂ'],
            ['en' => 'Guide', 'dv' => 'ގައިڈ'],
            ['en' => 'List', 'dv' => 'لسٹ'],
            ['en' => 'Opinion', 'dv' => 'ޚیال'],

            // Seasonal
            ['en' => 'Summer', 'dv' => 'ހੇ ମان'],
            ['en' => 'Monsoon', 'dv' => 'ވިއްساری މੌسم'],
            ['en' => 'Dry Season', 'dv' => 'ހިކި މੌسم'],
            ['en' => 'Seasonal', 'dv' => 'މੌسمی'],
            ['en' => 'Year-Round', 'dv' => 'އަހަރު ދުવަހު'],

            // Special
            ['en' => 'Award-Winning', 'dv' => 'އެوارڈ ލިބިފައިވާ'],
            ['en' => 'Must-Try', 'dv' => 'ކޮންމެހެން ރަހަ ބަލަންޖެހޭ'],
            ['en' => 'Classic', 'dv' => 'ކްލެسک'],
            ['en' => 'New', 'dv' => 'އާ'],
            ['en' => 'Trending', 'dv' => 'ٹرینڈنگ'],
            ['en' => 'Staff Pick', 'dv' => 'سٹاف ޕک'],
            ['en' => 'Reader Favorite', 'dv' => 'ކިیونوالوں کی پسند'],
        ];

        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'slug' => \Str::slug($tagName['en']),
            ]);
        }
    }
}
