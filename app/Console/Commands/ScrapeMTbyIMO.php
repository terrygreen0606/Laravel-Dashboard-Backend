<?php

namespace App\Console\Commands;

use App\Helpers\MTHelper;
use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use DateTime;
use Illuminate\Console\Command;

class ScrapeMTbyIMO extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:scrape-mt-imo {--extended=0} {--satelite=0} {--imo=0}';

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
     * Execute the console command. 9085388
     *
     * @return mixed
     */
    public function handle()
    {
        $extended = $this->option('extended');
        $satelite = $this->option('satelite');
        $imo = intval($this->option('imo'));

        if ($imo == 0) {
          $dayBefore = (new DateTime())->modify('-1 day')->format('Y-m-d');
          $fleets_imo_list = Vessel::select('imo')->distinct()->join('companies', 'vessels.company_id', '=', 'companies.id')
                  ->whereHas('networks', function ($w) {
                   $w->where('network_id', '<>', 4);
          })->pluck('imo');

          $nasa = Vessel::select('imo')->distinct()->join('companies', 'vessels.company_id', '=', 'companies.id')
                  ->whereHas('networks', function ($w) {
              $w->where('network_id', 4);
          })->pluck('imo');
          $merged = $fleets_imo_list->merge($nasa);
        } else {
          $merged = array($imo);
        }
        $count = 0;
        $count_updated = 0;
        $count_skipped = 0;
        $count_error = 0;
        $dateNow = new DateTime();
        foreach ($merged as $imo) {
            echo $count . "\n";
            echo $imo . "\n";
            $count++;
            if (strlen($imo) == 0) continue;
            $vessel = Vessel::where('imo', $imo)->first();
            //$photo = MTHelper::getVesselPhoto($vessel, $imo, true);
            //echo print_r($photo, true);
            //$particulars = MTHelper::getVesselTrack($vessel->imo);
            //echo print_r($particulars, true);
            if (!empty($vessel->ais_last_update)) {
                $dateLastUpdate = new DateTime();
                $last_update = $dateLastUpdate->setTimestamp(strtotime($vessel->ais_last_update));
                $interval = $dateNow->diff($last_update);
                if ($interval->i < 2)  {
                    echo $last_update->format('c');
                    echo "Last update: " . $interval->i . " minutes ago, skipping...\n";
                    $count_skipped++;
                    continue;
                }
            }
            if ($extended) {
                sleep(5); // extended responses limit frequency of requests
            } else {
            }
            $update = MTHelper::getVesselAIS($imo, $extended, $satelite, true);
            if ($update) {
                $count_updated++;
            } else {
                $count_error++;
            }
          $this->info('----------------');
          $this->info('Processed: ' . $count);
          $this->info(' Updated: ' . $count_updated);
          if ($count_error > 0) {
              $this->info('ERROR Updating: ' . $count_error);
          }
          if ($count_updated > 5) {
            break;
          }
        }
/*
        //Test Zones
        $vessels_model = Vessel::whereNotNull('latitude')->whereNotNull('longitude');
        $vessels_count = $vessels_model->count();
        $vessels = $vessels_model->get();
        foreach ($vessels as $vessel) {
            $vessel->zone_id = getGeoZoneID($vessel->latitude, $vessel->longitude);
            $vessel->save();
        }
        $this->line('Updated Vessel Zones: ' . $vessels_count);
*/
    }
}
