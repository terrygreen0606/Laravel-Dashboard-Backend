<?php

use Illuminate\Database\Seeder;

class SystemComponentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('system_components')->delete();
        DB::table('system_components')->insert([
            [
                'name' => 'Individuals',
                'code' => 'users'
            ],
            [
                'name' => 'Settings',
                'code' => 'settings'
            ],
            [
                'name' => 'Companies',
                'code' => 'companies'
            ],
            [
                'name' => 'Vessels',
                'code' => 'vessels'
            ],
            [
                'name' => 'Map',
                'code' => 'map'
            ],
            [
                'name' => 'Fleets',
                'code' => 'fleets'
            ],
            [
                'name' => 'Vendors',
                'code' => 'vendors'
            ],
            [
                'name' => 'System Reports',
                'code' => 'system_reports'
            ],
            [
                'name' => 'Clients',
                'code' => 'clients'
            ]
        ]);
    }
}
