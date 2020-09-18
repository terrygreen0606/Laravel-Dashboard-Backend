<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->delete();
        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'Administrator',
                'code' => 'ADMIN',
                'description' => 'Use this account with extreme caution. When using this account it is possible to cause irreversible damage to the system.'
            ],
            [
                'id' => 2,
                'name' => 'User',
                'code' => 'USER',
                'description' => ''
            ],
            [
                'id' => 3,
                'name' => 'QI Company',
                'code' => 'VENDOR',
                'description' => 'A QC user that can use the basic system map overview services. No advanced map features.'
            ],
            [
                'id' => 4,
                'name' => 'Coast Guard',
                'code' => 'COAST_GUARD',
                'description' => ''
            ],
            [
                'id' => 5,
                'name' => 'Duty Team',
                'code' => 'DUTY_TEAM',
                'description' => ''
            ],
            [
                'id' => 6,
                'name' => 'NASA User',
                'code' => 'NASA_USER',
                'description' => ''
            ],
            [
                'id' => 7,
                'name' => 'Point of Contact',
                'code' => 'POC',
                'description' => ''
            ]
        ]);
    }
}
