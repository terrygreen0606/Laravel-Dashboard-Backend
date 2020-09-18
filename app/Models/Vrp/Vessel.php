<?php

namespace App\Models\Vrp;

use Laravel\Scout\Searchable;

class Vessel extends \App\Models\BaseModel
{
    use Searchable;
    protected $table = 'vessels_data';
    protected $connection = "mysql_vrp";
    protected $guarded = [];

    const TYPES_TANK = [
        "TANK (Primary)",
        "TANK (Primary)/SMPEP",
        "TANK/SOPEP",
        "TANK (Secondary)",
        "TANK (Secondary)/SOPEP"
    ];
    const TYPES_NONTANK = [
        "NT",
        "SMPEP",
        "SOPEP",
        "NT/SMPEP",
        "NT/SOPEP"
    ];

    public static function exportData() {
        $vessels = self::where('vrp_deleted', 0)->with(['vrpPlan' => function($q) {
            $q->where('vrp_deleted', 0);
        }])->with(['OneQI' => function($q) {
            $q->where('vrp_deleted', 0);
        }])->with(['NavRest' => function($q) {
            $q->where('vrp_deleted', 0);
        }])->where('vrp_deleted', 0)->orderBy('vessel_status', 'asc')->get();

        return $vessels;
    }

    public static function exportCount() {
        $vessels = self::count();

        return $vessels;
    }

    public function toSearchableArray()
    {
        return array_only($this->toArray(), ['imo', 'official_number', 'mmsi', 'vessel_name']);
    }

    public function vrpPlan()
    {
        return $this->hasOne('App\Models\Vrp\VrpPlan', 'id', 'plan_number_id')->withDefault([
            'id' => -1,
            'plan_number' => ' - ',
            'plan_holder' => 'UNDEFINED',
            'plan_preparer' => 'UNDEFINED',
            'primary_smff' => 'UNDEFINED'
        ]);
    }

    public function Cotp()
    {
        return $this->belongsToMany('App\Models\Vrp\Cotp', 'vessel_cotp', 'vessel_id', 'vrp_cotp_id');
    }

    public function NavRest()
    {
        return $this->belongsToMany('App\Models\Vrp\NavRest', 'vessel_nav_rest', 'vessel_id', 'vrp_nav_rest_id');
    }

    public function QI()
    {
        return $this->belongsToMany('App\Models\Vrp\QualifiedIndividual', 'vessel_qi', 'vessel_id', 'vrp_qi_id');
    }

    public function OneQI()
    {
        return $this->QI()->latest();
    }

    public function Smff()
    {
        return $this->belongsToMany('App\Models\Vrp\Smff', 'vessel_smff', 'vessel_id', 'vrp_smff_id');
    }

    public function Osro()
    {
        return $this->belongsToMany('App\Models\Vrp\Osro', 'vessel_osro', 'vessel_id', 'vrp_osro_id');
    }

    public function OneOsro()
    {
        return $this->Osro()->latest();
    }
}
