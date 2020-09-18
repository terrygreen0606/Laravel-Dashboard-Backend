<?php

namespace App\Console\Commands;

use App\Models\Vessel;
use App\Models\VesselAISPositions;
use Illuminate\Console\Command;

class updateVesselTableAISData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:update-vessel-table-ais-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update vessels table AIS Data from vessel_ais_positions table';

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
        $ids = VesselAISPositions::distinct('vessel_id')->pluck('vessel_id');
        for ($i = 25; $i < count($ids); $i++) {
            $id = $ids[$i];
            $latest = VesselAISPositions::where('vessel_id', $id)->latest('timestamp')->first();
            echo $id . PHP_EOL;
            if (Vessel::find($id)->exists()) {
                Vessel::find($id)->update([
                    'ais_lat' => $latest->lat,
                    'ais_long' => $latest->lon,
                    'ais_heading' => $latest->heading,
                    'speed' => $latest->speed,
                    'ais_nav_status_id' => $latest->status,
                    'ais_timestamp' => $latest->timestamp,
                ]);
            }
        }
    }
}
