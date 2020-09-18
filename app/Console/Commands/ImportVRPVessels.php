<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Vessel;
use App\Models\Vrp\Vessel as VrpVessel;
use Illuminate\Console\Command;

class ImportVRPVessels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:import-vrp {--company_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Vessels from VRP data for Company specified.';

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
        $company = Company::find($this->option('company_id'));

        if (empty($company)) {
            die("COMPANY (--company_id=) " . $this->option('company_id') . " NOT FOUND");
        }

        $plan_number = $company->plan_number;

        if (empty($plan_number) || strlen($plan_number) == 0) {
            die("PLAN NUMBER FOR COMPANY " . $company->name . " (" . $this->option('company_id') . ") NOT FOUND");

        }

        $cdtVessels = Vessel::where('company_id', $company->id)->get();
        $cdtImos = $cdtVessels->pluck('imo')->toArray();
        $vrpVessels = VrpVessel::where('plan_number', $plan_number)->whereNotIn('imo', $cdtImos)->get();

        $vrpImos = $vrpVessels->pluck('imo')->toArray();

        $this->line('FOUND: ' . $vrpVessels->count() . ' VRP vessels');

        $this->line(print_r($vrpImos, true));

        foreach ($vrpVessels as $importVessel) {
            $vesselData = [
                'imo' => $importVessel->imo,
                'official_number' => $importVessel->official_number,
                'company_id' => $company->id,
                'name' => $importVessel->vessel_name,
                'vessel_type_id' => 12,

            ];
//            Vessel::create($vesselData);
        }

        $this->line('IMPORTED');
        return true;
    }
}
