<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            // Proteins
            ['name' => 'Tuna', 'name_dv' => 'ކަނޑުމަސް'],
            ['name' => 'Skipjack Tuna', 'name_dv' => 'ކަޅުބިލަމަސް'],
            ['name' => 'Yellowfin Tuna', 'name_dv' => 'ކެޔޮޅު'],
            ['name' => 'Reef Fish', 'name_dv' => 'ފަރު މަސް'],
            ['name' => 'Grouper', 'name_dv' => 'ފާނަ'],
            ['name' => 'Red Snapper', 'name_dv' => 'ރަތް މަސް'],
            ['name' => 'Chicken', 'name_dv' => 'ކުކުޅު'],
            ['name' => 'Egg', 'name_dv' => 'ބިސް'],
            ['name' => 'Dried Fish', 'name_dv' => 'ހިކިމަސް'],
            ['name' => 'Smoked Tuna', 'name_dv' => 'ވަޅޯމަސް'],

            // Vegetables
            ['name' => 'Onion', 'name_dv' => 'ފިޔާ'],
            ['name' => 'Garlic', 'name_dv' => 'ލޮނުމެދު'],
            ['name' => 'Ginger', 'name_dv' => 'އިނގުރު'],
            ['name' => 'Green Chili', 'name_dv' => 'ގިތެޔޮ މިރުސް'],
            ['name' => 'Red Chili', 'name_dv' => 'ރަތް މިރުސް'],
            ['name' => 'Curry Leaves', 'name_dv' => 'ހިކަނދިފަތް'],
            ['name' => 'Pandan Leaves', 'name_dv' => 'ރާނބާފަތް'],
            ['name' => 'Tomato', 'name_dv' => 'ޓޮމާޓޯ'],
            ['name' => 'Carrot', 'name_dv' => 'ކެރެޓް'],
            ['name' => 'Cabbage', 'name_dv' => 'ކެބެޖް'],
            ['name' => 'Eggplant', 'name_dv' => 'ބަށި'],
            ['name' => 'Breadfruit', 'name_dv' => 'ބަނބުކެޔޮ'],
            ['name' => 'Taro', 'name_dv' => 'އަލަ'],
            ['name' => 'Sweet Potato', 'name_dv' => 'ކަށިކެޔޮ'],
            ['name' => 'Drumstick', 'name_dv' => 'މުރަނގަ'],
            ['name' => 'Pumpkin', 'name_dv' => 'ބަރަބޯ'],
            ['name' => 'Moringa Leaves', 'name_dv' => 'މުރަނގަފަތް'],

            // Coconut Products
            ['name' => 'Coconut', 'name_dv' => 'ކާށި'],
            ['name' => 'Coconut Milk', 'name_dv' => 'ކާށިކިރު'],
            ['name' => 'Grated Coconut', 'name_dv' => 'ހުނި'],
            ['name' => 'Coconut Oil', 'name_dv' => 'ކާށި ތެޔޮ'],
            ['name' => 'Coconut Cream', 'name_dv' => 'ބޯ ކާށިކިރު'],

            // Grains & Starches
            ['name' => 'Rice', 'name_dv' => 'ހަނޑޫ'],
            ['name' => 'Rice Flour', 'name_dv' => 'ހަނޑޫ ފުށް'],
            ['name' => 'All-Purpose Flour', 'name_dv' => 'ފުށް'],
            ['name' => 'Roshi Flour', 'name_dv' => 'ރޮށި ފުށް'],
            ['name' => 'Semolina', 'name_dv' => 'ރަވާ'],

            // Spices
            ['name' => 'Turmeric', 'name_dv' => 'ރީނދޫ'],
            ['name' => 'Cumin', 'name_dv' => 'ދިރި'],
            ['name' => 'Coriander Seeds', 'name_dv' => 'ކޮތަނބިރި'],
            ['name' => 'Black Pepper', 'name_dv' => 'އަސޭމިރުސް'],
            ['name' => 'Cardamom', 'name_dv' => 'ކާފޫރު ތޮޅި'],
            ['name' => 'Cinnamon', 'name_dv' => 'ފޮނިތޮށި'],
            ['name' => 'Cloves', 'name_dv' => 'ކރަންފޫ'],
            ['name' => 'Fennel Seeds', 'name_dv' => 'ބޮޑު ދިރި'],
            ['name' => 'Fenugreek', 'name_dv' => 'އުލހިޔަ'],
            ['name' => 'Mustard Seeds', 'name_dv' => 'އަބީ'],
            ['name' => 'Curry Powder', 'name_dv' => 'ރިހާކުރު ހަވާދު'],
            ['name' => 'Rihaakuru Spice Mix', 'name_dv' => 'ރިހާކުރު ހަވާދު'],
            ['name' => 'Mas Huni Spice', 'name_dv' => 'މަސްހުނި ހަވާދު'],

            // Condiments & Sauces
            ['name' => 'Rihaakuru', 'name_dv' => 'ރިހާކުރު'],
            ['name' => 'Soy Sauce', 'name_dv' => 'ސޯޔާ ސޯސް'],
            ['name' => 'Fish Sauce', 'name_dv' => 'ފިޝް ސޯސް'],
            ['name' => 'Tamarind', 'name_dv' => 'އަސާރަ'],
            ['name' => 'Lime', 'name_dv' => 'ލުނބޯ'],
            ['name' => 'Maldive Fish', 'name_dv' => 'ދިވެހި މަސް'],

            // Sweeteners
            ['name' => 'Sugar', 'name_dv' => 'ހަކުރު'],
            ['name' => 'Palm Sugar', 'name_dv' => 'ދިޔާ ހަކުރު'],
            ['name' => 'Honey', 'name_dv' => 'މާމުއި'],
            ['name' => 'Coconut Toddy', 'name_dv' => 'ރާ'],

            // Fruits
            ['name' => 'Banana', 'name_dv' => 'ދޮންކެޔޮ'],
            ['name' => 'Papaya', 'name_dv' => 'ފަޅޯ'],
            ['name' => 'Mango', 'name_dv' => 'އަނބު'],
            ['name' => 'Watermelon', 'name_dv' => 'ކަރާ'],
            ['name' => 'Screwpine', 'name_dv' => 'ކާށިކެޔޮ'],

            // Others
            ['name' => 'Salt', 'name_dv' => 'ލޮނު'],
            ['name' => 'Water', 'name_dv' => 'ފެން'],
            ['name' => 'Vegetable Oil', 'name_dv' => 'ތެޔޮ'],
            ['name' => 'Butter', 'name_dv' => 'ބަޓަރު'],
            ['name' => 'Ghee', 'name_dv' => 'ގިތެޔޮ'],
            ['name' => 'Milk', 'name_dv' => 'ކިރު'],
            ['name' => 'Condensed Milk', 'name_dv' => 'ގެރިކިރު'],
            ['name' => 'Fresh Coriander', 'name_dv' => 'ކޮތަނބިރި ފަތް'],
            ['name' => 'Mint Leaves', 'name_dv' => 'ފުދީނާ'],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::firstOrCreate(
                ['name' => $ingredient['name']],
                $ingredient
            );
        }
    }
}
