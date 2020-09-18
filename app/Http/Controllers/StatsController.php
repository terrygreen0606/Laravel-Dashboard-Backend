<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function systemHeartBeat()
    {
        $categories = [];
        $series = [];
        $stroke = [
            'dashArray' => [0, 8, 5, 11]
        ];

        $created_vessels = [
            'name' => 'Created Vessels',
            'data' => []
        ];

        $created_companies = [
            'name' => 'Created Companies',
            'data' => []
        ];

        $created_individuals = [
            'name' => 'Created Individuals',
            'data' => []
        ];

        $ais_vessels = [
            'name' => 'AIS Updated Vessels',
            'data' => []
        ];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subDay($i);
            $categories[] = $date->format('d M');
            $created_vessels['data'][] = Vessel::whereDate('created_at', $date->toDateString())->count();
            $created_companies['data'][] = Company::whereDate('created_at', $date->toDateString())->count();
            $created_individuals['data'][] = User::whereDate('created_at', $date->toDateString())->count();
            $ais_vessels['data'][] = Vessel::whereDate('ais_timestamp', $date->toDateString())->count();
        }

        $series[] = $created_vessels;
        $series[] = $created_companies;
        $series[] = $created_individuals;
        $series[] = $ais_vessels;

        return [
            'categories' => $categories,
            'series' => $series,
            'stroke' => $stroke
        ];
    }

    public function activeVessels()
    {
        $labels = ['Active', 'Inactive'];
        $series = [
            Vessel::where('active', 1)->count(),
            Vessel::where('active', 0)->count()
        ];
        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    public function activeCompanies()
    {
        $labels = ['Active', 'Inactive'];
        $series = [
            Company::where('active', 1)->count(),
            Company::where('active', 0)->count()
        ];
        return [
            'labels' => $labels,
            'series' => $series
        ];
    }

    public function activeIndividuals()
    {
        $labels = ['Active', 'Inactive'];
        $series = [
            User::where('active', 1)->count(),
            User::where('active', 0)->count()
        ];
        return [
            'labels' => $labels,
            'series' => $series
        ];
    }
}
