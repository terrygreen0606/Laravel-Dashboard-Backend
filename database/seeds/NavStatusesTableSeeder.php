<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AisStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('nav_statuses')->delete();
        DB::table('nav_statuses')->insert([
            [
                'status_id' => '0',
                'value' => 'under way using engine',
            ],
            [
                'status_id' => '1',
                'value' => 'at anchor',
            ],
            [
                'status_id' => '2',
                'value' => 'not under command',
            ],
            [
                'status_id' => '3',
                'value' => 'restricted maneuverability',
            ],
            [
                'status_id' => '4',
                'value' => 'constrained by her draught',
            ],
            [
                'status_id' => '5',
                'value' => 'moored',
            ],
            [
                'status_id' => '6',
                'value' => 'aground',
            ],
            [
                'status_id' => '7',
                'value' => 'engaged in fishing',
            ],
            [
                'status_id' => '8',
                'value' => 'under way sailing',
            ],
            [
                'status_id' => '9',
                'value' => 'reserved for future amendment of navigational status for ships carrying DG, HS, or MP, or IMO hazard or pollutant category C, high-speed craft (HSC)',
            ],
            [
                'status_id' => '10',
                'value' => 'reserved for future amendment of navigational status for ships carrying dangerous goods (DG), harmful substances (HS) or marine pollutants (MP), or IMO hazard or pollutant category A, wing in ground (WIG)',
            ],
            [
                'status_id' => '11',
                'value' => 'power-driven vessel towing astern (regional use)',
            ],
            [
                'status_id' => '12',
                'value' => 'power-driven vessel pushing ahead or towing alongside (regional use)',
            ],
            [
                'status_id' => '13',
                'value' => 'reserved for future use',
            ],
            [
                'status_id' => '14',
                'value' => 'AIS-SART (active), MOB-AIS, EPIRB-AIS',
            ],
            [
                'status_id' => '15',
                'value' => 'undefined',
            ],
            [
                'status_id' => '95',
                'value' => 'Base Station',
            ],
            [
                'status_id' => '96',
                'value' => 'Class B',
            ],
            [
                'status_id' => '97',
                'value' => 'SAR Aircraft',
            ],
            [
                'status_id' => '98',
                'value' => 'Aid to Navigation',
            ],
            [
                'status_id' => '99',
                'value' => 'Class B',
            ]
        ]);
    }
}
