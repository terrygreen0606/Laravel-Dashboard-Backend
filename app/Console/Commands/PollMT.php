<?php

namespace App\Console\Commands;

use App\Helpers\MTHelper;
use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use App\Models\VesselAisPoll;
use App\Models\VesselAisApiCost;
use App\Models\User;
use DateTime;
use Illuminate\Console\Command;

class PollMT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:poll-mt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for vessel MT polls that need running and run';


    public $mode = 'local';

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
        $log_file = "/var/log/cdt/mttest.log";

        $dateNow = new DateTime();
        $polls = VesselAisPoll::where([
            ['status', '=', 1],
            ['last_run_date', '<', $dateNow]
        ])->orWhereNull('last_run_date')->get();
        echo system("ulimit -a") . " FORK LIMIT\n";
        echo count($polls) . " polls\n";

        $vessel_pos_ids = [];
        $poll_ids_with_pos = [];
        foreach ($polls as $poll) {
            if (!empty($poll->last_run_date)) {
                $dateLastUpdate = new DateTime();
                echo $poll->last_run_date;
                $last_update = $dateLastUpdate->setTimestamp(strtotime($poll->last_run_date));
                $interval = $last_update->diff($dateNow);

                $intervalInSeconds = (new DateTime())->setTimeStamp(0)->add($interval)->getTimeStamp();
                $intervalInMinutes = $intervalInSeconds/60;

                echo "MINUTES: " . $intervalInMinutes . " INTERVAL: " . $poll->repeating_interval_minutes . "\n";
                if ($intervalInMinutes < $poll->repeating_interval_minutes)  {
                    echo "SKIP\n";
                    continue;
                }
            }
            $vessels = $poll->vessels()->get();
            echo count($vessels) . " vessels\n";
            $networks = $poll->networks()->get();
            $network_vessels = Vessel::
                select(['vessels.*'])
                ->distinct()
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->whereIn('nc.network_id', $networks->pluck('id')->toArray())
                ->get();
            echo count($network_vessels) . " network vessels\n";
            $fleets = $poll->fleets()->get();
            $fleet_vessels = Vessel::
              select(['vessels.*'])
              ->distinct()
              ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
              ->whereIn('vf.fleet_id', $fleets->pluck('id')->toArray())
              ->get();
            echo count($fleet_vessels) . " fleet vessels\n";
            $vessel_ids = $network_vessels->pluck('id')->toArray();
            $vessels = $vessels->union($network_vessels)->union($fleet_vessels)->unique()
                ->slice(0, 2); // !!!TODO temporarily only process first 2 in batch
            $total_vessels = count($vessels);
            echo $total_vessels . " vessels total\n";

            $poll->status = 2;
            $poll->batch_number = $poll->batch_number + 1;
            $poll->batch_count = $total_vessels;
            $poll->batch_processed_count = 0;
            $poll->batch_success_count = 0;
            $poll->last_run_date = $dateNow;
            $poll->save();

            if (count($vessels) > 1) {
                switch ($poll->type_id) {
                case VesselAisApiCost::POS_TER_EXTENDED :
                    return;
                case VesselAisApiCost::POS_SAT_EXTENDED :
                    return;
                }
            }
            $dateLastUpdate = new DateTime();
            echo "\n\n" . "START: " . $dateLastUpdate->format('h:i:s v') . "\n\n";
            $count = 0;
            $mode = "server";
            foreach ($vessels as $vessel) {
                switch ($mode) {
                case "local" :
                    MTHelper::processPoll($vessel->imo, $poll->id);
                    break;

                default :
                    // rudimentary multi-threading
                    exec("php artisan cdt:mt-imo --imo=" . $vessel->imo . " --poll=" . $poll->id . "  >> " . $log_file . " 2>&1 &");
                    break;
                }
                if ($poll->type_id != VesselAisApiCost::PARTICULARS &&
                    $poll->type_id != VesselAisApiCost::PHOTOS) {
                    $vessel_pos_ids[] = $vessel->id;
                }
                $count++;
                $dateLastUpdate = new DateTime();
                if (($count % 10) == 0) echo "COUNT: " . $count . " TIME: " . $dateLastUpdate->format('h:i:s v');
            }
            if ($poll->type_id != VesselAisApiCost::PARTICULARS &&
                $poll->type_id != VesselAisApiCost::PHOTOS) {
                $poll_ids_with_pos[] = $poll->id;
            }

            $dateLastUpdate = new DateTime();
            echo "\n\n" . "FINISH: " . $dateLastUpdate->format('h:i:s v') . "\n\n";
            if ($poll->repeating) {

            } else {
                $poll->status = 0;
            }
            $poll->save();
        }
        if (count($poll_ids_with_pos) == 0) {
            $poll->status = 1;
            $poll->save();
            return;
        }
        // determine whether to do this here or in last process
        $poll = VesselAisPoll::find($poll->id);
        if ($poll->status != 4) {
            $poll->status = 1;
            $poll->save();
        }
        $seconds = 0;
        while ($seconds < (60 * 5)) {
            sleep(2);
            $seconds++;
            $polls = VesselAisPoll::whereIn('id', $poll_ids_with_pos)->get();
            $finished = 0;
            foreach ($polls as $poll) {
                if ($poll->batch_processed_count >= $poll->batch_count) {
                    $finished++;
                }
            }
            $dateLastUpdate = new DateTime();
            echo "\n\n" . "PROCESSING: " . $dateLastUpdate->format('h:i:s.v') . "\n\n";
            $this->line($finished . " / " . count($poll_ids_with_pos));
            if ($finished >= count($poll_ids_with_pos) || $seconds > (60 * 2)) {
                break;
            }
        }
        $dateLastUpdate = new DateTime();
        echo "\n\n" . "GEO START: " . $dateLastUpdate->format('h:i:s.v') . "\n\n";
        $vessels_model = Vessel::whereNotNull('latitude')->whereNotNull('longitude')
            ->whereIn('id', $vessel_pos_ids);
        $vessels_count = $vessels_model->count();
        $vessels = $vessels_model->get();
        foreach ($vessels as $vessel) {
            $vessel->zone_id = getGeoZoneID($vessel->latitude, $vessel->longitude);
            $vessel->save();
        }
        $this->line('Updated Vessel Zones: ' . $vessels_count);
        $dateLastUpdate = new DateTime();
        echo "\n\n" . "GEO FINISH: " . $dateLastUpdate->format('h:i:s.v') . "\n\n";

    }
}
