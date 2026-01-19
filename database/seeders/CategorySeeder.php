<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories for the food publication.
     *
     * This seeder is used in both production and development environments
     * to create the full category hierarchy.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['en' => 'Spotlight', 'dv' => 'ސްޕޮޓްލައިޓް'],
                'slug' => 'spotlight',
                'description' => ['en' => 'Breaking news, highlights, and trending stories'],
                'children' => [
                    ['name' => ['en' => 'Headlines', 'dv' => 'ހެޑްލައިންސް'], 'slug' => 'headlines', 'description' => ['en' => 'Top stories and breaking news']],
                    ['name' => ['en' => 'New Openings & Closings', 'dv' => 'އާ ތަންތަނާއި ބަންދުވުން'], 'slug' => 'new-openings-closings', 'description' => ['en' => 'Restaurant and cafe openings and closures']],
                    ['name' => ['en' => 'Brand & Retail Updates', 'dv' => 'ބްރޭންڈ އަދި ރީٹیل އަޕްڈیٹ'], 'slug' => 'brand-retail-updates', 'description' => ['en' => 'Updates from food brands and retail']],
                    ['name' => ['en' => 'Hotel & Resort Dining News', 'dv' => 'ہوٹل އަދި ރިزورٹ ڈائننگ ޚަބަރު'], 'slug' => 'hotel-resort-dining-news', 'description' => ['en' => 'News from hotel and resort restaurants']],
                    ['name' => ['en' => 'Events & Pop-Ups', 'dv' => 'އިवेنٹްސް އަދި ޕੋپ-اپس'], 'slug' => 'events-pop-ups', 'description' => ['en' => 'Food events, pop-ups, and festivals']],
                    ['name' => ['en' => 'People Moves', 'dv' => 'ޕیپل މੂވސ'], 'slug' => 'people-moves', 'description' => ['en' => 'Industry appointments and career moves']],
                    ['name' => ['en' => 'Trends & Talk', 'dv' => 'ٹرینڈސް އަދި ٹوک'], 'slug' => 'trends-talk', 'description' => ['en' => 'What everyone is talking about']],
                    ['name' => ['en' => 'Seasonal Watch', 'dv' => 'ސیزنل واچ'], 'slug' => 'seasonal-watch', 'description' => ['en' => 'Seasonal highlights and limited-time offerings']],
                ],
            ],
            [
                'name' => ['en' => 'Features', 'dv' => 'ފީޗަރސް'],
                'slug' => 'features',
                'description' => ['en' => 'In-depth stories and long-form journalism'],
                'children' => [
                    ['name' => ['en' => 'Cover Story', 'dv' => 'ކވر سٹوری'], 'slug' => 'cover-story', 'description' => ['en' => 'Our flagship feature stories']],
                    ['name' => ['en' => 'Food Industry & Business', 'dv' => 'ފုڈ އینڈسٹری އަދި ބიზنეს'], 'slug' => 'food-industry-business', 'description' => ['en' => 'Industry analysis and business stories']],
                    ['name' => ['en' => 'Trend Watch', 'dv' => 'ٹرینڈ واچ'], 'slug' => 'trend-watch', 'description' => ['en' => 'Emerging trends in food']],
                    ['name' => ['en' => 'Deep Dives', 'dv' => 'ڈیپ ڈائوز'], 'slug' => 'deep-dives', 'description' => ['en' => 'Comprehensive explorations of food topics']],
                    ['name' => ['en' => 'Ingredients Spotlight', 'dv' => 'އިންگریڈینٹ سپاٹ لائٹ'], 'slug' => 'ingredients-spotlight', 'description' => ['en' => 'Focus on specific ingredients']],
                    ['name' => ['en' => 'Chef\'s Table', 'dv' => 'ޝیف ٹیبل'], 'slug' => 'chefs-table', 'description' => ['en' => 'Stories from professional kitchens']],
                    ['name' => ['en' => 'Community Stories', 'dv' => 'ކمیونٹی سٹوریز'], 'slug' => 'community-stories', 'description' => ['en' => 'Stories from the food community']],
                    ['name' => ['en' => 'Food Tech & Innovation', 'dv' => 'ފুڈ ٹیک އަދި اینوویشن'], 'slug' => 'food-tech-innovation', 'description' => ['en' => 'Technology and innovation in food']],
                ],
            ],
            [
                'name' => ['en' => 'People', 'dv' => 'މީހުން'],
                'slug' => 'people',
                'description' => ['en' => 'Profiles and stories of food personalities'],
                'children' => [
                    ['name' => ['en' => 'Chefs & Cooks', 'dv' => 'ޝެފުންނާއި ކައްކާމީހުން'], 'slug' => 'chefs-cooks', 'description' => ['en' => 'Professional chefs and cooks']],
                    ['name' => ['en' => 'Home Cooks', 'dv' => 'ގޭގައި ކައްކާ މީހުން'], 'slug' => 'home-cooks', 'description' => ['en' => 'Home cooking enthusiasts']],
                    ['name' => ['en' => 'Bakers & Pastry Artists', 'dv' => 'ބेکرز އަދި ޕیسٹری آرٹسٹس'], 'slug' => 'bakers-pastry-artists', 'description' => ['en' => 'Bakers and pastry professionals']],
                    ['name' => ['en' => 'Bartenders & Baristas', 'dv' => 'بارٹینڈرز އަދި بریسٹاز'], 'slug' => 'bartenders-baristas', 'description' => ['en' => 'Drink specialists and coffee experts']],
                    ['name' => ['en' => 'Farmers & Fishers', 'dv' => 'ދަނڈުވެރިންނާއި މަސްވެރިން'], 'slug' => 'farmers-fishers', 'description' => ['en' => 'Those who grow and catch our food']],
                    ['name' => ['en' => 'Founders & Restaurateurs', 'dv' => 'ފައؤنڈرز އަދި ریسٹورینٹیئرز'], 'slug' => 'founders-restaurateurs', 'description' => ['en' => 'Business owners and entrepreneurs']],
                    ['name' => ['en' => 'Food Creators', 'dv' => 'ފުڈ ކریٹرز'], 'slug' => 'food-creators', 'description' => ['en' => 'Food influencers and content creators']],
                    ['name' => ['en' => 'Rising Stars', 'dv' => 'ރައزنگ سٹارز'], 'slug' => 'rising-stars', 'description' => ['en' => 'Up-and-coming talent in food']],
                ],
            ],
            [
                'name' => ['en' => 'Culture', 'dv' => 'ސަގާފަތް'],
                'slug' => 'culture',
                'description' => ['en' => 'Food culture, traditions, and heritage'],
                'children' => [
                    ['name' => ['en' => 'Maldivian Food Traditions', 'dv' => 'ދިވެހި ކެއުމުގެ އާދަކާދަ'], 'slug' => 'maldivian-food-traditions', 'description' => ['en' => 'Traditional Maldivian food customs']],
                    ['name' => ['en' => 'Island Food Stories', 'dv' => 'ރަށުގެ ކެއުމުގެ ވާހަކަ'], 'slug' => 'island-food-stories', 'description' => ['en' => 'Food stories from the islands']],
                    ['name' => ['en' => 'Festive & Seasonal', 'dv' => 'ފެسٹیو އަދި سیزنل'], 'slug' => 'festive-seasonal', 'description' => ['en' => 'Holiday and seasonal food traditions']],
                    ['name' => ['en' => 'Heritage Recipes & Memories', 'dv' => 'ތަރިކައިގެ ރެސިپیاں އަދި ހަނދާންތައް'], 'slug' => 'heritage-recipes-memories', 'description' => ['en' => 'Recipes passed down through generations']],
                    ['name' => ['en' => 'Language of Food', 'dv' => 'ކެއުމުގެ ބަސް'], 'slug' => 'language-of-food', 'description' => ['en' => 'Food terminology and linguistics']],
                    ['name' => ['en' => 'Food & Identity', 'dv' => 'ކެއުމާއި ذات'], 'slug' => 'food-identity', 'description' => ['en' => 'How food shapes who we are']],
                    ['name' => ['en' => 'Street Food & Snacks', 'dv' => 'ސްٹریٹ ފުڈ އަދި ސنیکسް'], 'slug' => 'street-food-snacks', 'description' => ['en' => 'Street food culture and snacking']],
                    ['name' => ['en' => 'Drink Culture', 'dv' => 'ބުއیموں کی ثقافت'], 'slug' => 'drink-culture', 'description' => ['en' => 'Beverage traditions and culture']],
                ],
            ],
            [
                'name' => ['en' => 'Travel', 'dv' => 'ދަތުރު'],
                'slug' => 'travel',
                'description' => ['en' => 'Food-focused travel guides and stories'],
                'children' => [
                    ['name' => ['en' => 'Atolls & Islands Guides', 'dv' => 'އަތޮޅު އަދި ރަށް ގައิڈ'], 'slug' => 'atolls-islands-guides', 'description' => ['en' => 'Food guides for Maldivian atolls and islands']],
                    ['name' => ['en' => 'Resort Dining', 'dv' => 'ރިزورٹ ڈائننگ'], 'slug' => 'resort-dining', 'description' => ['en' => 'Dining at Maldivian resorts']],
                    ['name' => ['en' => 'City Food Guides', 'dv' => 'ސٹی ފوڈ ގައڈ'], 'slug' => 'city-food-guides', 'description' => ['en' => 'Urban food guides']],
                    ['name' => ['en' => 'Cafés & Hangouts', 'dv' => 'ކیفے އަދި ہینگ آؤٹ'], 'slug' => 'cafes-hangouts', 'description' => ['en' => 'Best places to hang out']],
                    ['name' => ['en' => 'Food Trails', 'dv' => 'ފوڈ ٹریلز'], 'slug' => 'food-trails', 'description' => ['en' => 'Curated food trails and routes']],
                    ['name' => ['en' => 'Local Markets', 'dv' => 'ލੋکل مارکیٹ'], 'slug' => 'local-markets', 'description' => ['en' => 'Markets and where to shop']],
                    ['name' => ['en' => 'Best for…', 'dv' => 'ބެسٹ ފور...'], 'slug' => 'best-for', 'description' => ['en' => 'Best places for specific occasions']],
                    ['name' => ['en' => 'South Asia & Middle East', 'dv' => 'ދެކުނު އޭशيया އަދި މެދުއިރުމަތި'], 'slug' => 'south-asia-middle-east', 'description' => ['en' => 'Food travel in the region']],
                ],
            ],
            [
                'name' => ['en' => 'Reviews', 'dv' => 'ރިވިއު'],
                'slug' => 'reviews',
                'description' => ['en' => 'Restaurant, product, and service reviews'],
                'children' => [
                    ['name' => ['en' => 'Restaurant Reviews', 'dv' => 'ރެسٹورینٹ ریویو'], 'slug' => 'restaurant-reviews', 'description' => ['en' => 'In-depth restaurant evaluations']],
                    ['name' => ['en' => 'Café Reviews', 'dv' => 'کیفے ریویو'], 'slug' => 'cafe-reviews', 'description' => ['en' => 'Café and coffee shop reviews']],
                    ['name' => ['en' => 'Resort Restaurant Reviews', 'dv' => 'ރިزورٹ ریسٹورینٹ ریویو'], 'slug' => 'resort-restaurant-reviews', 'description' => ['en' => 'Reviews of resort dining']],
                    ['name' => ['en' => 'Street Food Finds', 'dv' => 'سٹریٹ فوڈ فائنڈز'], 'slug' => 'street-food-finds', 'description' => ['en' => 'Street food discoveries']],
                    ['name' => ['en' => 'New Openings', 'dv' => 'އާ ތަންތަން'], 'slug' => 'new-openings', 'description' => ['en' => 'First looks at new restaurants']],
                    ['name' => ['en' => 'Menu Highlights', 'dv' => 'މینیو ہائی لائٹس'], 'slug' => 'menu-highlights', 'description' => ['en' => 'Standout dishes and menus']],
                    ['name' => ['en' => 'Product Reviews', 'dv' => 'پروڈکٹ ریویو'], 'slug' => 'product-reviews', 'description' => ['en' => 'Food product reviews']],
                    ['name' => ['en' => 'Kitchen Tools Reviews', 'dv' => 'کچن ٹولز ریویو'], 'slug' => 'kitchen-tools-reviews', 'description' => ['en' => 'Kitchen equipment and tools']],
                    ['name' => ['en' => 'Business Reviews', 'dv' => 'ބിزنس ریویو'], 'slug' => 'business-reviews', 'description' => ['en' => 'Food business and service reviews']],
                ],
            ],
            [
                'name' => ['en' => 'Recipes', 'dv' => 'ރެسیپی'],
                'slug' => 'recipes',
                'description' => ['en' => 'Recipes and cooking guides'],
                'children' => [
                    ['name' => ['en' => 'Quick & Easy', 'dv' => 'އަވަސް އަދި ފަސޭހަ'], 'slug' => 'quick-easy', 'description' => ['en' => 'Recipes under 30 minutes']],
                    ['name' => ['en' => 'Weeknight Meals', 'dv' => 'ویک نائٹ میلز'], 'slug' => 'weeknight-meals', 'description' => ['en' => 'Easy dinners for busy nights']],
                    ['name' => ['en' => 'Traditional Maldivian', 'dv' => 'ދިވެހި ސަގާފީ'], 'slug' => 'traditional-maldivian', 'description' => ['en' => 'Classic Maldivian recipes']],
                    ['name' => ['en' => 'Healthy & Light', 'dv' => 'ސިއްحی އަދި ލائٹ'], 'slug' => 'healthy-light', 'description' => ['en' => 'Nutritious and light meals']],
                    ['name' => ['en' => 'Baking & Desserts', 'dv' => 'ބیکنگ އަދި ڈیزرٹ'], 'slug' => 'baking-desserts', 'description' => ['en' => 'Sweet treats and baked goods']],
                    ['name' => ['en' => 'Drinks', 'dv' => 'ބުއیންތައް'], 'slug' => 'drinks', 'description' => ['en' => 'Beverages and refreshments']],
                    ['name' => ['en' => 'Seafood', 'dv' => 'ކނޑުމަސް'], 'slug' => 'seafood', 'description' => ['en' => 'Fish and seafood dishes']],
                    ['name' => ['en' => 'Special Diets', 'dv' => 'خاص ڈائیٹ'], 'slug' => 'special-diets', 'description' => ['en' => 'Dietary-specific recipes']],
                    ['name' => ['en' => 'By Ingredient', 'dv' => 'ބާވަتް ގِت'], 'slug' => 'by-ingredient', 'description' => ['en' => 'Recipes organized by ingredient']],
                    ['name' => ['en' => 'By Occasion', 'dv' => 'މުނާسبات ގِت'], 'slug' => 'by-occasion', 'description' => ['en' => 'Recipes for special occasions']],
                ],
            ],
        ];

        $order = 1;
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    ...$categoryData,
                    'order' => $order++,
                ]
            );

            $childOrder = 1;
            foreach ($children as $childData) {
                Category::firstOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        ...$childData,
                        'parent_id' => $parent->id,
                        'order' => $childOrder++,
                    ]
                );
            }
        }
    }
}
