<?php

namespace App\Console\Commands;

use App\Models\AisStatus;
use App\Models\Vessel;
use DateTime;
use Illuminate\Console\Command;
use KubAT\PhpSimple\HtmlDomParser;

class ScrapeMT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:scrape-mt-mmsi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape MT and update vessels with data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dayBefore = (new DateTime())->modify('-1 day')->format('Y-m-d');
//        $fleets_imo_list = Vessel::whereDate('ais_last_update', '<=', $dayBefore)->orWhereNull('ais_last_update')->whereHas('fleets')->pluck('imo');
        $fleets_mmsi_list = Vessel::whereDate('ais_last_update', '<=', $dayBefore)->orWhereNull('ais_last_update')->whereHas('networks')->pluck('mmsi');
        foreach ($fleets_mmsi_list as $mmsi) {
            //https://www.marinetraffic.com/en/ais/details/ships/imo:9560259
            $link = 'https://www.marinetraffic.com/en/ais/details/ships/mmsi:' . $mmsi;
            try {
                $html = HtmlDomParser::str_get_html(file_get_contents($link));
                $unparset = $html->find('a[class=details_data_link]')[0]->text();
                $lat = preg_replace("/[^0-9\.\-]/", '', explode('/', $unparset)[0]);
                $lon = preg_replace("/[^0-9\.\-]/", '', explode('/', $unparset)[1]);
//            $mmsi = preg_replace("/[^0-9]/", '', $html->find('b[class=text-primary text-dark]')[2]->text());
                $this->comment($mmsi);
                $imo_unparsed = $html->find('b[class=text-primary text-dark]');

                if (empty($imo_unparsed))
                    $imo = 0;
                else
                    $imo = preg_replace("/[^0-9]/", '', $imo_unparsed[0]->text());
                $unparset_update_time = $html->find('strong')[4]->text();
                $vessel_name = $html->find('h1[class=font-200 no-margin]')[0]->text();
                $start_update_parse = strrpos($unparset_update_time, '2019') - 1;

                if ($start_update_parse !== -1)
                    $parsed_update_time = substr($unparset_update_time, $start_update_parse + 1);
                else
                    $parsed_update_time = $unparset_update_time;
                $update_time = str_replace(' ', 'T', substr(trim($parsed_update_time), 0, 16)) . ':00';

                $mt_status = $html->find('strong')[8]->text();
                if ($mt_status === 'Underway Using Engine')
                    $mt_status = 'under way using engine';
                elseif ($mt_status === 'Underway By Sail')
                    $mt_status = 'under way sailing';
                $ais_status = AisStatus::where('value', strtolower($mt_status))->first();
                $status = $ais_status ? $ais_status->status_id : null;
                $speed = preg_replace("/[^0-9\.]/", '', explode('/', $html->find('strong')[9]->text())[0]) ?: 0;
                $rot = preg_replace("/[^0-9\.]/", '', explode('/', $html->find('strong')[9]->text())[1]) ?: 0;
                $vessel = Vessel::where('mmsi', $mmsi);
                $vessel->update([
                    'ais_provider_id' => 1,
                    'imo' => $imo,
                    'name' => $vessel_name,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'speed' => $speed,
                    'course' => $rot,
                    'heading' => $rot, //just a fix
                    'ais_status_id' => $status,
                    'ais_last_update' => $update_time
                ]);
                $this->info('----------------');
                $this->info('Name: ' . $vessel->first()->vessel_name);
                $this->info('IMO: ' . $imo);
                $this->info('MMSI: ' . $mmsi);
                $this->info('Latitude: ' . $lat);
                $this->info('Longitude: ' . $lon);
                $this->info('Speed: ' . $speed);
                $this->info('Course: ' . $rot);
                $this->info('Nav Status Code: ' . $mt_status . ' / ' . $status);
                $this->info('Last Update: ' . $update_time);
                $this->info('----------------');
            } catch (\Exception $error) {
                $this->warn($mmsi . ' error updating');
//                $vessel = Vessel::where('mmsi', $mmsi);
//                $vessel->update([
//                    'showOnMap' => 0
//                ]);
            }
        }
    }
}
