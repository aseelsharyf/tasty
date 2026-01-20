<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Volume
            ['name' => 'Cup', 'name_dv' => 'ޖޯޑު', 'abbreviation' => 'cup', 'abbreviation_dv' => 'ޖޯޑު'],
            ['name' => 'Tablespoon', 'name_dv' => 'މޭޒުމަތީ ސަމުސާ', 'abbreviation' => 'tbsp', 'abbreviation_dv' => 'މ.ސ'],
            ['name' => 'Teaspoon', 'name_dv' => 'ސައިސަމުސާ', 'abbreviation' => 'tsp', 'abbreviation_dv' => 'ސ.ސ'],
            ['name' => 'Milliliter', 'name_dv' => 'މިލިލީޓަރު', 'abbreviation' => 'ml', 'abbreviation_dv' => 'މލ'],
            ['name' => 'Liter', 'name_dv' => 'ލީޓަރު', 'abbreviation' => 'L', 'abbreviation_dv' => 'ލ'],
            ['name' => 'Fluid Ounce', 'name_dv' => 'ފްލުއިޑް އައުންސް', 'abbreviation' => 'fl oz', 'abbreviation_dv' => 'ފ.އ'],

            // Weight
            ['name' => 'Gram', 'name_dv' => 'ގްރާމް', 'abbreviation' => 'g', 'abbreviation_dv' => 'ގ'],
            ['name' => 'Kilogram', 'name_dv' => 'ކިލޯ', 'abbreviation' => 'kg', 'abbreviation_dv' => 'ކގ'],
            ['name' => 'Ounce', 'name_dv' => 'އައުންސް', 'abbreviation' => 'oz', 'abbreviation_dv' => 'އ'],
            ['name' => 'Pound', 'name_dv' => 'ޕައުންޑް', 'abbreviation' => 'lb', 'abbreviation_dv' => 'ޕ'],

            // Count/Pieces
            ['name' => 'Piece', 'name_dv' => 'އެތިކޮޅެއް', 'abbreviation' => 'pc', 'abbreviation_dv' => 'އެތި'],
            ['name' => 'Whole', 'name_dv' => 'އެއްކޮށް', 'abbreviation' => 'whole', 'abbreviation_dv' => 'އެއްކޮށް'],
            ['name' => 'Slice', 'name_dv' => 'ފޮތި', 'abbreviation' => 'slice', 'abbreviation_dv' => 'ފޮތި'],
            ['name' => 'Clove', 'name_dv' => 'ލޮނުމެދެއް', 'abbreviation' => 'clove', 'abbreviation_dv' => 'ފިޔަ'],
            ['name' => 'Bunch', 'name_dv' => 'ބޮނޑި', 'abbreviation' => 'bunch', 'abbreviation_dv' => 'ބޮނޑި'],
            ['name' => 'Handful', 'name_dv' => 'މުށެއް', 'abbreviation' => 'handful', 'abbreviation_dv' => 'މުށް'],
            ['name' => 'Sprig', 'name_dv' => 'ފަތެއް', 'abbreviation' => 'sprig', 'abbreviation_dv' => 'ފަތް'],
            ['name' => 'Stalk', 'name_dv' => 'ގަނޑެއް', 'abbreviation' => 'stalk', 'abbreviation_dv' => 'ގަނޑު'],
            ['name' => 'Head', 'name_dv' => 'ބޮލެއް', 'abbreviation' => 'head', 'abbreviation_dv' => 'ބޯ'],
            ['name' => 'Can', 'name_dv' => 'ދަޅެއް', 'abbreviation' => 'can', 'abbreviation_dv' => 'ދަޅު'],

            // Size-based
            ['name' => 'Small', 'name_dv' => 'ކުޑަ', 'abbreviation' => 'sm', 'abbreviation_dv' => 'ކ'],
            ['name' => 'Medium', 'name_dv' => 'މެދު', 'abbreviation' => 'med', 'abbreviation_dv' => 'މ'],
            ['name' => 'Large', 'name_dv' => 'ބޮޑު', 'abbreviation' => 'lg', 'abbreviation_dv' => 'ބ'],

            // Approximate
            ['name' => 'Pinch', 'name_dv' => 'ކުޑަ ކުޑަ', 'abbreviation' => 'pinch', 'abbreviation_dv' => 'ކކ'],
            ['name' => 'Dash', 'name_dv' => 'ކުޑަ ކޮޅެއް', 'abbreviation' => 'dash', 'abbreviation_dv' => 'ކޮޅެއް'],
            ['name' => 'To Taste', 'name_dv' => 'ރަހަލާ ވަރަށް', 'abbreviation' => 'to taste', 'abbreviation_dv' => 'ރަހައަށް'],
            ['name' => 'As Needed', 'name_dv' => 'ބޭނުންވާ ވަރަށް', 'abbreviation' => 'as needed', 'abbreviation_dv' => 'ބޭނުންވާ'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['name' => $unit['name']],
                $unit
            );
        }
    }
}
