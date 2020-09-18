<?php

use Illuminate\Database\Seeder;

class NetworksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('networks')->delete();

        DB::table('networks')->insert([
            [
                'name' => 'OPA-90 Network',
                'code' => 'opa_90'
            ],
            [
                'name' => 'Donjon Navy Contract',
                'code' => 'dnc'
            ],
            [
                'name' => 'NASA Support',
                'code' => 'ns'
            ],
            [
                'name' => 'NASA Potential Response Asset',
                'code' => 'npnc'
            ]
        ]);
    }
}
