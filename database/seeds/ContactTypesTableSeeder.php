<?php

use Illuminate\Database\Seeder;

class ContactTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contact_types')->delete();
        DB::table('contact_types')->insert([
            [
                'id' => 1,
                'name' => 'Primary'
            ],
            [
                'id' => 2,
                'name' => 'Secondary'
            ],
            [
                'id' => 3,
                'name' => 'Finance'
            ],
            [
                'id' => 4,
                'name' => 'Operations'
            ],
            [
                'id' => 6,
                'name' => 'DPA'
            ],
            [
                'id' => 7,
                'name' => 'Alternate DPA'
            ]
        ]);
    }
}
