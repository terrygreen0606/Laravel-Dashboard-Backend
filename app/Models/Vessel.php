<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use App\Models\VesselFleets;

use Symfony\Component\Process\Process as Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Vessel extends BaseModel
{
    use Searchable;
    protected $table = 'vessels';
    protected $guarded = [];

    const FIELDS_CDT = '
        v1.id AS id,
        -1 AS vrpid,
        0 AS vrp_import,
        (v1.active AND c1.active) AS djs_active,
        c1.networks_active,
        vs.id IS NOT NULL AS capabilies_active,
        "" AS vrp_primary_smff,
        1 AS djs,
        0 AS linked,
        0 AS auth,
        v1.imo,
        v1.official_number,
        v1.name,
        0 AS vrp_count,
        0 AS vrp_status,
        c1.plan_number AS vrp_plan_number,
        c1.id AS company_id,
        c1.has_photo AS company_has_photo,
        c1.name AS company_name,
        c1.plan_number AS company_plan_number,
        c1.active AS company_active,
        0 AS vrp_vessel_is_tank,
        t.name AS vrp_type,
        v1.tanker,
        vs.id IS NOT NULL  AS resource_provider,
        v1.active,
        v1.active AS coverage,
        v1.smff_service_id IS NOT NULL OR c1.smff_service_id IS NOT NULL AS response,
        v1.vessel_type_id,
        v1.has_photo,
        v1.mmsi, dead_weight,
        tanker,
        deck_area,
        oil_tank_volume,
        oil_group,
        primary_poc_id,
        secondary_poc_id,
        sat_phone_primary,
        sat_phone_secondary,
        email_primary,
        email_secondary,
        v1.created_at,
        v1.updated_at
    ';

    const UNION_FIELDS_CDT = '
        v1.id AS id,
        vrpv.id AS vrpid,
        0 AS vrp_import,
        (v1.active AND c1.active) AS djs_active,
        c1.networks_active,
        vs.id IS NOT NULL AS capabilies_active,
        p.primary_smff AS vrp_primary_smff,
        1 AS djs,
        vrpv.id IS NOT NULL AS linked,
        vrpv.vessel_status = "Authorized" AS auth,
        v1.imo,
        v1.official_number,
        v1.name,
        1 AS vrp_count,
        vrpv.vessel_status AS vrp_status,
        c1.plan_number AS vrp_plan_number,
        c1.id AS company_id,
        c1.id AS vrp_company_id,
        c1.name AS company_name,
        c1.plan_number AS company_plan_number,
        c1.active AS company_active,
        vrpv.vessel_is_tank LIKE "%Tank%" AS vrp_vessel_is_tank,
        t.name AS vrp_type,
        v1.tanker AS tanker,
        vs.id IS NOT NULL AS resource_provider,
        v1.active,
        v1.active AS coverage,
        v1.smff_service_id IS NOT NULL OR c1.smff_service_id IS NOT NULL AS response,
        v1.vessel_type_id,
        v1.mmsi,
        dead_weight,
        tanker,
        deck_area,
        oil_tank_volume,
        oil_group,
        primary_poc_id,
        secondary_poc_id,
        sat_phone_primary,
        sat_phone_secondary,
        email_primary,
        email_secondary,
        v1.created_at,
        v1.updated_at
    ';

    const UNION_FIELDS_VRP = '
        -1 AS id,
        vrpv2.id AS vrpid,
        1 AS vrp_import,
        0 AS djs_active,
        0 AS networks_active,
        0 AS capabilies_active,
        p.primary_smff AS vrp_primary_smff,
        0 AS djs,
        0 AS linked,
        vrpv2.vessel_status = "Authorized" AS auth,
        vrpv2.imo,
        vrpv2.official_number,
        vessel_name AS name,
        1 AS vrp_count,
        vrpv2.vessel_status AS vrp_status,
        vrpv2.plan_number_id AS vrp_plan_number,
        NULL AS company_id,
        c2.id AS vrp_company_id,
        NULL AS company_name,
        NULL AS company_plan_number,
        NULL AS company_active,
        vrpv2.vessel_is_tank LIKE "%Tank%" AS vrp_vessel_is_tank,
        vessel_type AS vrp_type,
        vrpv2.vessel_is_tank LIKE "%Tank%" AS tanker,
        0 AS resource_provider,
        0 AS active, p.primary_smff LIKE "%donjon%" AS coverage,
        0 AS response,
        NULL AS vessel_type_id,
        NULL AS  mmsi,
        NULL AS  dead_weight,
        NULL AS  tanker,
        NULL AS  deck_area,
        NULL AS  oil_tank_volume,
        NULL AS  oil_group,
        NULL AS  primary_poc_id,
        NULL AS  secondary_poc_id,
        NULL AS  sat_phone_primary,
        NULL AS  sat_phone_secondary,
        NULL AS  email_primary,
        NULL AS  email_secondary,
        vrpv2.created_at,
        vrpv2.updated_at
    ';

    // const EXCLUDE_VRP_WHERE_CLAUSE = "((vrpv2.imo IS NOT NULL AND vrpv2.imo NOT IN (SELECT imo FROM `cdt_dev`.`vessels` AS vx WHERE vx.imo IS NOT NULL)) OR (vrpv2.imo IS NULL AND vrpv2.official_number IS NOT NULL AND vrpv2.official_number NOT IN (SELECT official_number FROM `cdt_dev`.`vessels` AS vx2 WHERE vx2.official_number IS NOT NULL)))";

    public static function rebuildIndex() {
        try {
            $process = new Process('php ../artisan cdt:rebuild-vessel-index');
            $process->start();
        } catch (\Exception $error) {
            // TODO: raise flag here
        }
    }


    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */

    public function vessels_fleets(){

        return $this->hasMany(VesselFleets::class);

    }

    public function toSearchableArray()
    {
        return Arr::only($this->toArray(), ['imo', 'mmsi', 'name']);
    }

    public function tracingHistory()
    {
        return $this->hasMany(VesselHistoricalTrack::class);
    }

    public function fleets()
    {
        return $this->belongsToMany(Fleet::class, 'vessels_fleets');
    }

    public function vendors()
    {
        //return $this->belongsToMany(Vendor::class, 'vessels_vendors');
        return $this->belongsToMany(Company::class, 'vessels_vendors','vessel_id','company_id');
    }



    public function notes()
    {
        return $this->hasMany(VesselNotes::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withDefault([
            'id' => -1,
            'plan_number' => '',
            'name' => 'Unknown',
            'active' => 0,
        ]);
    }

    public function primaryPoc()
    {
        return $this->hasOne(CompanyContact::class, 'id', 'primary_poc_id');
    }

    public function secondaryPoc()
    {
        return $this->hasOne(CompanyContact::class, 'id', 'secondary_poc_id');
    }

    public function navStatus()
    {
        return $this->hasOne(NavStatus::class, 'status_id', 'ais_nav_status_id')->withDefault([
            'status_id' => -1,
            'value' => 'Unknown'
        ]);
    }

    public function type()
    {
        return $this->hasOne(VesselType::class, 'id', 'vessel_type_id')->withDefault([
            'id' => -1,
            'name' => 'Not Defined',
            'ais_category_id' => null
        ]);
    }

    public function sisters()
    {
        return $this->hasMany(__CLASS__, 'lead_sister_ship_id', 'id');
    }

    public function childs()
    {
        return $this->hasMany(__CLASS__, 'lead_ship_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'ais_provider_id', 'id');
    }

    public function smffCapability()
    {
        return $this->hasOne(Capability::class, 'id', 'smff_service_id');
    }

    public function networks()
    {
        return $this
            ->belongsToMany(Network::class, 'network_companies', 'company_id', 'network_id', 'company_id')
            ->select(['companies.*', 'networks.code'])
            ->join('companies', 'company_id', '=', 'companies.id')
            ->where('companies.networks_active', 1);
    }

    public function smff() {
        $smff_service_id = $this->smff_service_id;
        if ($smff_service_id) {
            $capability = Capability::where(['id' => $smff_service_id, 'status' => 1])->first();
            if (!empty($capability)) {
                return $capability->valuesAsAssoc();
            }
        }

        return null;
    }


    public function zone()
    {
        $latest = $this
            ->hasMany(VesselAISPositions::class, 'vessel_id', 'id')
            ->latest('timestamp')
            ->first();

        // NOTE: if not in the vessel_ais_positions table, return 'Unknown' for zone name
        if (empty($latest)) {
            $latest = new Vessel;
            $latest->zone_id = NULL;

            return $latest->hasOne(Zone::class, 'id', 'zone_id')->withDefault([
                'id' => -2,
                'name' => 'Unknown',
                'code' => 'NOT-TESTED'
            ]);
        }

        return $latest->hasOne(Zone::class, 'id', 'zone_id')->withDefault([
            'id' => -1,
            'name' => 'Outside US EEZ',
            'code' => 'NOT-TESTED'
        ]);
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            //$model->updateIndexVessels(false);
            self::rebuildIndex();
        });
        self::updating(function ($model) {
            if ($model->isDirty('latitude') || $model->isDirty('longitude')) {
                //test zone and save
                $model->zone_id = getGeoZoneID($model->latitude, $model->longitude);
            }
        });
    }

    public function vrpPlan()
    {
        return $this->hasOne(Vrp\Vessel::class, 'imo', 'imo');
    }


    public function track()
    {
        return $this->hasMany(VesselHistoricalTrack::class);
    }

    public function currentLocation()
    {
        return $this->track->where('is_current_location', 1)->first();
    }

    public function aisDetails()
    {
        return $this->hasOne(VesselAISDetails::class);
    }

    public function aisPositions()
    {
        return $this->hasMany(VesselAISPositions::class);
    }

    public function lastaisPositions()
    {
        return $this->hasOne(VesselAISPositions::class)->latest();
    }

}



