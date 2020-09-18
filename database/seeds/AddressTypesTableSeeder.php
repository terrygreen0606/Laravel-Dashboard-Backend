<?php

use Illuminate\Database\Seeder;

class AddressTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('address_types')->delete();
        DB::table('address_types')->insert([
            [
                'id' => 1,
                'name' => 'Primary'
            ],
            [
                'id' => 2,
                'name' => 'Billing'
            ],
            [
                'id' => 3,
                'name' => 'Branches'
            ]
        ]);
    }
}
