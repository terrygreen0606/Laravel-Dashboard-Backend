<?php

namespace App\Console\Commands;

use App\Helpers\MTHelper;
use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use App\Models\VesselAisPoll;
use App\Models\VesselAisApiCost;
use DateTime;
use Illuminate\Console\Command;

class MTbyIMO extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:mt-imo {--imo=0} {--poll=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MT API Request and update vessels with data';

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
      $imo = intval($this->option('imo'));
      $poll_id = intval($this->option('poll'));

      MTHelper::processPoll($imo, $poll_id);
    }
}
