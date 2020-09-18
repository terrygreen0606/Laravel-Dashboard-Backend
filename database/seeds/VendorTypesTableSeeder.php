<?php

use Illuminate\Database\Seeder;

class VendorTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('vendor_types')->delete();
        DB::table('vendor_types')->insert([
            [
                'id' => 1,
                'name' => 'Society'
            ],
            [
                'id' => 2,
                'name' => 'H&M Insurer'
            ],
            [
                'id' => 3,
                'name' => 'P&I Club'
            ],
            [
                'id' => 4,
                'name' => 'QI Company'
            ],
            [
                'id' => 5,
                'name' => 'Response'
            ],
            [
                'id' => 6,
                'name' => 'Damage Stability Certificate Provider'
            ]
        ]);
    }
}
