<?php

namespace App\Console\Commands;

use App\Helpers\MTHelper;
use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use App\Models\VesselAisApiCost;
use App\Models\User;
use DateTime;
use Illuminate\Console\Command;

class PollIMO extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:poll-mt-imo {--imo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add vessel for continuous MT poll';

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
        $imo = $this->option('imo');

        $user = User::find(5000);
        $vessel = Vessel::where('imo', $imo)->first();

        //$photo = MTHelper::getVesselPhoto($vessel, $imo, true);
        //echo print_r($photo, true);
        $result = MTHelper::addVesselPoll($user, VesselAisApiCost::POS_TER_SIMPLE, [$vessel->id],
            true, 2);
        echo print_r($result, true);
    }
}
