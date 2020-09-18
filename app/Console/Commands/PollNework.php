<?php

namespace App\Console\Commands;

use App\Helpers\MTHelper;
use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\Network;
use App\Models\VesselHistoricalTrack;
use App\Models\VesselAisApiCost;
use App\Models\User;
use DateTime;
use Illuminate\Console\Command;

class PollNework extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:poll-mt-network {--network=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add vessels in network for continuous MT poll';

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
        $network = $this->option('network');

        $user = User::find(5000);
        $network = Network::where('code', $network)->first();

        //$photo = MTHelper::getVesselPhoto($vessel, $imo, true);
        //echo print_r($photo, true);
        $result = MTHelper::addNetworkPoll($user, VesselAisApiCost::POS_TER_SIMPLE, [$network->id],
            true, 60);
        echo print_r($result, true);
    }
}
