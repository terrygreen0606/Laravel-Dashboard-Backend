<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(AisStatusesTableSeeder::class);
//        $this->call(VesselTypesTableSeeder::class);
        $this->call(AddressTypesTableSeeder::class);
        $this->call(ContactTypesTableSeeder::class);
        $this->call(FleetsTableSeeder::class);
        $this->call(VendorTypesTableSeeder::class);
        $this->call(ZoneTableSeeder::class);
        $this->call(MapGeoLayerTableSeeder::class);
        $this->call(NetworksTableSeeder::class);
        $this->call(SystemComponentsTableSeeder::class);
    }
}
