<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vessel;

class RemoveDuplicateVessel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:replace-ais-timestamp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //
        $vessels = Vessel::all();
        foreach($vessels as $vessel)
        {
            $vessel->ais_timestamp = '0000-00-00 00:00:00';
            $vessel->save();
        }
        echo "success!";
    }
}
