<?php

use Illuminate\Database\Seeder;

class ZoneTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('zones')->truncate();

        DB::table('zones')->insert([
            [
                'code' => 'Anchorage',
                'name' => 'Anchorage',
                'url_geojson' => '/storage/zone_tests/Anchorage.json'
            ],
            [
                'code' => 'Boston',
                'name' => 'Boston',
                'url_geojson' => '/storage/zone_tests/Boston.json'
            ],
            [
                'code' => 'Buffalo',
                'name' => 'Buffalo',
                'url_geojson' => '/storage/zone_tests/Buffalo.json'
            ],
            [
                'code' => 'Charleston',
                'name' => 'Charleston',
                'url_geojson' => '/storage/zone_tests/Charleston.json'
            ],
            [
                'code' => 'Corpus_Christi',
                'name' => 'Corpus Christi',
                'url_geojson' => '/storage/zone_tests/Corpus_Christi.json'
            ],
            [
                'code' => 'Delaware_Bay',
                'name' => 'Delaware Bay',
                'url_geojson' => '/storage/zone_tests/Delaware_Bay.json'
            ],
            [
                'code' => 'Detroit',
                'name' => 'Detroit',
                'url_geojson' => '/storage/zone_tests/Detroit.json'
            ],
            [
                'code' => 'Guam',
                'name' => 'Guam',
                'url_geojson' => '/storage/zone_tests/Guam.json'
            ],
            [
                'code' => 'Hampton_Roads',
                'name' => 'Hampton Roads',
                'url_geojson' => '/storage/zone_tests/Hampton_Roads.json'
            ],
            [
                'code' => 'Honolulu',
                'name' => 'Honolulu',
                'url_geojson' => '/storage/zone_tests/Honolulu.json'
            ],
            [
                'code' => 'Houston_Galveston',
                'name' => 'Houston Galveston',
                'url_geojson' => '/storage/zone_tests/Houston_Galveston.json'
            ],
            [
                'code' => 'Jacksonville',
                'name' => 'Jacksonville',
                'url_geojson' => '/storage/zone_tests/Jacksonville.json'
            ],
            [
                'code' => 'Juneau',
                'name' => 'Juneau',
                'url_geojson' => '/storage/zone_tests/Juneau.json'
            ],
            [
                'code' => 'Key_West',
                'name' => 'Key West',
                'url_geojson' => '/storage/zone_tests/Key_West.json'
            ],
            [
                'code' => 'Lake_Michigan',
                'name' => 'Lake Michigan',
                'url_geojson' => '/storage/zone_tests/Lake_Michigan.json'
            ],
            [
                'code' => 'Long_Island',
                'name' => 'Long Island',
                'url_geojson' => '/storage/zone_tests/Long_Island.json'
            ],
            [
                'code' => 'Los_Angeles',
                'name' => 'Los Angeles',
                'url_geojson' => '/storage/zone_tests/Los_Angeles.json'
            ],
            [
                'code' => 'Lower_Mississippi',
                'name' => 'Lower Mississippi',
                'url_geojson' => '/storage/zone_tests/Lower_Mississippi.json'
            ],
            [
                'code' => 'Miami',
                'name' => 'Miami',
                'url_geojson' => '/storage/zone_tests/Miami.json'
            ],
            [
                'code' => 'Mobile',
                'name' => 'Mobile',
                'url_geojson' => '/storage/zone_tests/Mobile.json'
            ],
            [
                'code' => 'New_Orleans',
                'name' => 'New Orleans',
                'url_geojson' => '/storage/zone_tests/New_Orleans.json'
            ],
            [
                'code' => 'New_York',
                'name' => 'New York',
                'url_geojson' => '/storage/zone_tests/New_York.json'
            ],
            [
                'code' => 'North_Carolina',
                'name' => 'North Carolina',
                'url_geojson' => '/storage/zone_tests/North_Carolina.json'
            ],
            [
                'code' => 'Northern_New_England',
                'name' => 'Northern New England',
                'url_geojson' => '/storage/zone_tests/Northern_New_England.json'
            ],
            [
                'code' => 'Ohio_Valley',
                'name' => 'Ohio Valley',
                'url_geojson' => '/storage/zone_tests/Ohio_Valley.json'
            ],
            [
                'code' => 'Portland',
                'name' => 'Portland',
                'url_geojson' => '/storage/zone_tests/Portland.json'
            ],
            [
                'code' => 'San_Diego',
                'name' => 'San Diego',
                'url_geojson' => '/storage/zone_tests/San_Diego.json'
            ],
            [
                'code' => 'San_Francisco',
                'name' => 'San Francisco',
                'url_geojson' => '/storage/zone_tests/San_Francisco.json'
            ],
            [
                'code' => 'San_Juan',
                'name' => 'San Juan',
                'url_geojson' => '/storage/zone_tests/San_Juan.json'
            ],
            [
                'code' => 'Sault_St_Marie',
                'name' => 'Sault St Marie',
                'url_geojson' => '/storage/zone_tests/Sault_St_Marie.json'
            ],
            [
                'code' => 'Seattle',
                'name' => 'Seattle',
                'url_geojson' => '/storage/zone_tests/Seattle.json'
            ],
            [
                'code' => 'Southeastern_New_England',
                'name' => 'Southeastern New England',
                'url_geojson' => '/storage/zone_tests/Southeastern_New_England.json'
            ],
            [
                'code' => 'St_Petersburg',
                'name' => 'St Petersburg',
                'url_geojson' => '/storage/zone_tests/St_Petersburg.json'
            ],
            [
                'code' => 'Upper_Mississippi',
                'name' => 'Upper Mississippi',
                'url_geojson' => '/storage/zone_tests/Upper_Mississippi.json'
            ]
        ]);
    }
}
