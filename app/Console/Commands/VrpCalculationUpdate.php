<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vrp_Calcs\CountriesDjs;
use App\Models\Vrp_Calcs\PlanPreparerDjs;
use App\Models\Vrp_Calcs\RegionShipsDjs;
use App\Models\Vrp_Calcs\RegionTonnageDjs;
use App\Models\Vrp_Calcs\RegionVesselsDjs;
use App\Models\Vrp_Calcs\TankNontankDjs;
use App\Models\Vrp\VrpPlan;
use App\Models\Vrp\Vessel;

class VrpCalculationUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vrp:update-calcs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update vrp_calcs database all tables';

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
        // Country Vessels Percent Update
        $djsCountries = CountriesDjs::all();
        $countryResults = [];
        $totalVesselCount = 0;
        foreach($djsCountries as $djsCountry)
        {
            $countryName = $djsCountry->country_name;
            $vesselCount = Vessel::whereHas('VrpPlan', function ($q) use ($countryName) {
                $q->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized')
                    ->where('holder_country', $countryName);
            })->where('vessel_type', 'not like', '%Barge%')
                ->where('vessel_status', 'Authorized')
                ->count();
            
            $totalVesselCount += $vesselCount;
            $countryResults[] = [
                'country_name' => $countryName,
                'vessels' => $vesselCount
            ];
        }
        foreach($countryResults as $countryResult)
        {
            $percent = $countryResult['vessels'] * 100 / $totalVesselCount;
            CountriesDjs::where('country_name', $countryResult['country_name'])
                    ->update([
                        'vessels' => $countryResult['vessels'],
                        'percent' => $percent,
                        'last_update' => VrpPlan::min('updated_at')
                    ]);
        }
        // End Coutry Vessels Percent Update

        // Plan Preparer Vessels Percent Update
        $companies = [
            "WITT O'BRIEN'S",
            "GALLAGHER MARINE SYSTEMS",
            "ECM MARITIME SERVICES, LLC",
            "HUDSON MARINE MANAGEMENT SERVICES",
            "FOREFRONT EMERGENCY MANAGEMENT, LP",
            "COLONIAL COMPLIANCE SYSTEMS, INC.",
        ];

        $planPreparerResults = [];
        $totalVesselCount = 0;
        foreach($companies as $company)
        {
            $vesselCount = Vessel::whereHas('VrpPlan', function ($q) use ($company) {
                $q->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized')
                    ->where('plan_preparer', $company);
            })->where('vessel_type', 'not like', '%Barge%')
                ->where('vessel_status', 'Authorized')
                ->count();

            $totalVesselCount += $vesselCount;
            $planPreparerResults[] = [
                'plan_preparer' => $company,
                'vessels' => $vesselCount
            ];
            
            foreach($planPreparerResults as $planPreparerResult)
            {
                $percent = $planPreparerResult['vessels'] * 100 / $totalVesselCount;
                if(PlanPreparerDjs::where('plan_preparer', $planPreparerResult['plan_preparer'])->first()) {
                    PlanPreparerDjs::where('plan_preparer', $planPreparerResult['plan_preparer'])
                        ->update([
                            'vessels' => $planPreparerResult['vessels'],
                            'percent' => $percent,
                            'last_update' => VrpPlan::min('updated_at')
                        ]);
                } else {
                    PlanPreparerDjs::where('plan_preparer', $planPreparerResult['plan_preparer'])
                        ->create([
                            'plan_preparer' => $planPreparerResult['plan_preparer'],
                            'vessels' => $planPreparerResult['vessels'],
                            'percent' => $percent,
                            'last_update' => VrpPlan::min('updated_at')
                        ]);
                }
            }
        }
        // End Plan Preparer Vessels Percent Update

        // Region Djs
        $regions = [
            'ASIA',
            'EUROPE',
            'AMERICAS',
            'MIDDLE EAST'
        ];

        $totalShips = 0;
        $totalTonnage = 0;
        $totalVessels = 0;
        $regionResults = [];
        foreach($regions as $region)
        {
            $ships = Vessel::whereHas('VrpPlan', function ($q) use ($region) {
                $q->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized')
                    ->where('region', $region);
            })->where('vessel_type', 'not like', '%Barge%')
                ->where('vessel_status', 'Authorized')
                ->count();
            
            $totalShips += $ships;

            $tonnage = Vessel::whereHas('VrpPlan', function ($q) use ($region) {
                $q->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized')
                    ->where('region', $region);
            })->where('vessel_status', 'Authorized')
                ->sum('tonnage');

            $totalTonnage += $tonnage;

            $vessels = Vessel::whereHas('VrpPlan', function ($q) use ($region) {
                $q->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized')
                    ->where('region', $region);
            })->where('vessel_status', 'Authorized')
                ->count();
            
            $totalVessels += $vessels;

            $regionResults[] = [
                'region' => $region,
                'ships' => $ships,
                'tonnage' => $tonnage,
                'vessels' => $vessels,
            ];
        }
        
        foreach($regionResults as $regionResult)
        {
            $shipsPercent = $regionResult['ships'] * 100 / $totalShips;
            RegionShipsDjs::where('region', $regionResult['region'])
                ->update([
                    'ships' => $regionResult['ships'],
                    'percent' => $shipsPercent,
                    'last_update' => VrpPlan::min('updated_at')
                ]);

            $tonnagePercent = $regionResult['tonnage'] * 100 / $totalTonnage;
            RegionTonnageDjs::where('region', $regionResult['region'])
                ->update([
                    'tonnage' => $regionResult['tonnage'],
                    'percent' => $tonnagePercent,
                    'last_update' => VrpPlan::min('updated_at')
                ]);

            $vesselPercent = $regionResult['vessels'] * 100 / $totalVessels;
            RegionVesselsDjs::where('region', $regionResult['region'])
                ->update([
                    'vessels' => $regionResult['vessels'],
                    'percent' => $vesselPercent,
                    'last_update' => VrpPlan::min('updated_at')
                ]);
        }
        // End Region Djs

        // Tank And Non Tank Djs
        $planTypes = [
            'Tank',
            'Non-Tank'
        ];

        $planTypeResults = [];
        $totalPlanTypeCount = 0;

        foreach($planTypes as $planType)
        {
            $vrpPlans = Vessel::whereHas('VrpPlan', function ($q) use ($planType) {
                $q->where('plan_type', $planType)
                    ->where('primary_smff', 'DONJON-SMIT LLC')
                    ->where('vrp_deleted', 0)
                    ->where('status', 'Authorized');
            })->where('vessel_status', 'Authorized')
                ->count();

            $totalPlanTypeCount += $vrpPlans;
            $planTypeResults[] = [
                'plan_type' => $planType,
                'vessels' => $vrpPlans
            ];
        }

        foreach($planTypeResults as $planTypeResult)
        {
            $percent = $planTypeResult['vessels'] * 100 / $totalPlanTypeCount;
            TankNontankDjs::where('plan_type', $planTypeResult['plan_type'])
                ->update([
                    'vessels' => $planTypeResult['vessels'],
                    'percent' => $percent,
                    'last_update' => VrpPlan::min('updated_at')
                ]);
        }
        // End Tank And Non Tank Djs
    }
}
