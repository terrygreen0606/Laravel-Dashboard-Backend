<?php

use Illuminate\Database\Seeder;

class FleetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('fleets')->delete();

        DB::table('fleets')->insert([
            [
                'name' => 'Military Sealift Command',
                'code' => 'msc',
                'providerFleetId' => -1,
                'internal' => 0,
            ]
        ]);
    }
}
