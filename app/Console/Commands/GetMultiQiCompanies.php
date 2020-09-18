<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VesselVendor;
use App\Models\Vessel;
use App\Models\Company;

class GetMultiQiCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:multi-qi';

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
        $companies = Company::where([['active', 1],['vendor_active', 0], ['qi_id', 0]])->get();
        // echo count($companies);
        foreach($companies as $company)
        {
            echo $company->id. '   ' . $company->name . "\n";
        }
        // $companies = Company::where([['vendor_type', 3], ['vendor_active', 1]])->get();
        
        // $companyIds = [];
        // $i = 0;
        // foreach($companies as $company)
        // {
        //     $vessels = VesselVendor::where('company_id', $company->id)->get();
        //     $companyIds[] = [];
        //     foreach($vessels as $vessel)
        //     {
        //         $v = Vessel::where('id', $vessel->vessel_id)->first();
        //         $vesses = isset($v) ? $v->company_id : 0;

        //         // if (($key = array_search($vesses, $companyIds[$i])) == false) {
        //         //     array_push($companyIds[$i], $vesses);
        //         // }
        //         if(!in_array($vesses, $companyIds[$i])) {
        //             array_push($companyIds[$i], $vesses);
        //         }
        //     }
        //     $i++;
        // }

        // $doubleCompanies = [];
        // $count = count($companyIds);
        // for($i = 0; $i<$count - 1; $i++) {
        //     for($j = $i+1; $j<$count; $j++) {
        //         $re = array_intersect($companyIds[$i], $companyIds[$j]);
        //         $sameValue = array_intersect($doubleCompanies, $re);
        //         $diff = array_diff($re, $sameValue);
        //         $doubleCompanies = array_merge($doubleCompanies, $diff);
        //     }
        // }

        // $doubleCompanyNames = [];
        // foreach($doubleCompanies as $doubleCompany)
        // {
        //     $doubleCompanyName = Company::where('id', $doubleCompany)->first()->name;
        //     echo $doubleCompany. '   ' . $doubleCompanyName . "\n";
        // }
        // echo implode(" ", $doubleCompanyNames)."\n";
        // echo implode(" ",$doubleCompanies);
    }
}
