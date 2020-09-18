<?php

use Illuminate\Database\Seeder;

class MapGeoLayerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('map_geo_layers')->truncate();

        DB::table('map_geo_layers')->insert([
            [
                'code' => 'cotp',
                'name' => 'COTP Zones Layer',
                'url_geojson' => '/storage/COTP.geojson'
            ],
            [
                'code' => 'us_eez',
                'name' => 'US EEZ',
                'url_geojson' => '/storage/US_EEZ.geojson'
            ],
            [
                'code' => 'territorial_sea',
                'name' => 'Territorial Sea',
                'url_geojson' => '/storage/Territorial_Sea.geojson'
            ],
            [
                'code' => 'contiguous_zone',
                'name' => 'Contiguous Zone',
                'url_geojson' => '/storage/Contiguous_Zone.geojson'
            ]
        ]);
    }
}
