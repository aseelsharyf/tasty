<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    /**
     * Seed tags for a food publication.
     *
     * Note: Categories are handled by CategorySeeder which runs in ProductionSeeder.
     */
    public function run(): void
    {
        $this->createTags();
    }

    private function createTags(): void
    {
        $tags = [
            // Cuisine types
            ['en' => 'Maldivian', 'dv' => 'ދިވެހި'],
            ['en' => 'Asian', 'dv' => 'އޭޝިއަން'],
            ['en' => 'Indian', 'dv' => 'އިންडियན'],
            ['en' => 'Sri Lankan', 'dv' => 'ލަންކާ'],
            ['en' => 'Thai', 'dv' => 'ތައި'],
            ['en' => 'Japanese', 'dv' => 'ޖަޕާން'],
            ['en' => 'Chinese', 'dv' => 'ޗައިނީސް'],
            ['en' => 'Italian', 'dv' => 'އިޓަލީ'],
            ['en' => 'Mediterranean', 'dv' => 'މެڈิٹެރੇنియن'],
            ['en' => 'Middle Eastern', 'dv' => 'މެދުއިރުމަތި'],
            ['en' => 'American', 'dv' => 'އެމެरिکާ'],
            ['en' => 'Mexican', 'dv' => 'މެކ्ਸికੋ'],
            ['en' => 'French', 'dv' => 'ފްރެންچ'],
            ['en' => 'Korean', 'dv' => 'ކੋရियন'],
            ['en' => 'Vietnamese', 'dv' => 'ވియެٹनाम'],

            // Dietary
            ['en' => 'Vegetarian', 'dv' => 'ވެޖيٹేरियن'],
            ['en' => 'Vegan', 'dv' => 'ވީگن'],
            ['en' => 'Gluten-Free', 'dv' => 'ގްލూٹن ފްރީ'],
            ['en' => 'Halal', 'dv' => 'ހަލާލް'],
            ['en' => 'Dairy-Free', 'dv' => 'ڈیری ފްރީ'],
            ['en' => 'Keto', 'dv' => 'ކީٹੋ'],
            ['en' => 'Low-Carb', 'dv' => 'ލੋ ކާބް'],
            ['en' => 'Healthy', 'dv' => 'ސިއްحی'],

            // Meal types
            ['en' => 'Breakfast', 'dv' => 'ހެނދުނުގެ ނާستى'],
            ['en' => 'Brunch', 'dv' => 'ބްރަންچ'],
            ['en' => 'Lunch', 'dv' => 'މެންدوپهر'],
            ['en' => 'Dinner', 'dv' => 'ރޭگانގެ ކެއުން'],
            ['en' => 'Snacks', 'dv' => 'ސްنیکްسް'],
            ['en' => 'Appetizers', 'dv' => 'އެپیٹائزر'],
            ['en' => 'Main Course', 'dv' => 'މެއިން ކੋრسް'],
            ['en' => 'Dessert', 'dv' => 'ފޮނި ކާނާ'],
            ['en' => 'Drinks', 'dv' => 'ބުއިންތައް'],

            // Cooking methods
            ['en' => 'Grilled', 'dv' => 'ފިހެފައި'],
            ['en' => 'Fried', 'dv' => 'ތެލުލައިފައި'],
            ['en' => 'Baked', 'dv' => 'ފިهެفައި'],
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
            ['en' => 'Budget-Friendly', 'dv' => 'ބަجیٹ فرینڈلی'],
            ['en' => 'Luxury', 'dv' => 'ލަގްژری'],
            ['en' => 'Casual', 'dv' => 'ކیجوال'],
            ['en' => 'Romantic', 'dv' => 'ރومینٹک'],
            ['en' => 'Family-Friendly', 'dv' => 'އާއިلے فرینڈلی'],
            ['en' => 'Solo Dining', 'dv' => 'ސੋلو ڈائننگ'],
            ['en' => 'Group Dining', 'dv' => 'ގްރੂپ ڈائننگ'],

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
            ['en' => 'Long Read', 'dv' => 'ލونگ ریڈ'],
            ['en' => 'Quick Tip', 'dv' => 'ކوئک ٹپ'],
            ['en' => 'How-To', 'dv' => 'ހައު-ٹੂ'],
            ['en' => 'Guide', 'dv' => 'ގައިڈ'],
            ['en' => 'List', 'dv' => 'لسٹ'],
            ['en' => 'Opinion', 'dv' => 'ޚیال'],

            // Seasonal
            ['en' => 'Summer', 'dv' => 'ހੇ މான'],
            ['en' => 'Monsoon', 'dv' => 'ވިއްساری މੌسم'],
            ['en' => 'Dry Season', 'dv' => 'ހިކި މੌسم'],
            ['en' => 'Seasonal', 'dv' => 'މੌسمی'],
            ['en' => 'Year-Round', 'dv' => 'އަހަރު ދުވަހު'],

            // Special
            ['en' => 'Award-Winning', 'dv' => 'އެوارڈ ލިބިފައިވާ'],
            ['en' => 'Must-Try', 'dv' => 'ކޮންމެހެން ރަހަ ބަލަންޖެހޭ'],
            ['en' => 'Classic', 'dv' => 'ކްލެسک'],
            ['en' => 'New', 'dv' => 'އާ'],
            ['en' => 'Trending', 'dv' => 'ٹرینڈنگ'],
            ['en' => 'Staff Pick', 'dv' => 'سٹاف ޕک'],
            ['en' => 'Reader Favorite', 'dv' => 'ކیونوالوں کی پسند'],
        ];

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(
                ['slug' => \Str::slug($tagName['en'])],
                ['name' => $tagName]
            );
        }
    }
}
