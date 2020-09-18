<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Fleet;
use App\Http\Resources\CompanyAddressMapInfoResource;
use App\Http\Resources\CompanyAddressMapResource;
use App\Http\Resources\CompanyMapInfoResource;
use App\Http\Resources\MapGeoLayerResource;
use App\Http\Resources\UserAddressMapInfoResource;
use App\Http\Resources\UserAddressMapResource;
use App\Http\Resources\UserMapInfoResource;
use App\Http\Resources\VesselMapInfoResource;
use App\Http\Resources\VesselTrackResource;
use App\Models\MapGeoLayer;
use App\Models\Network;
use App\Models\Capability;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use Carbon\Carbon;

use function count;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public static function getFilteredVessels($filters)
    {
        $map_filters = json_decode($filters);

        if (is_object($map_filters)) {
            $fleets = is_array($map_filters->fleets) ? $map_filters->fleets : [];
            $networks = is_array($map_filters->networks) ? $map_filters->networks : [];
            $smff_selected = is_array($map_filters->smff_selected) ? $map_filters->smff_selected : [];
            $smff_operator = $map_filters->smff_operator ? $map_filters->smff_operator : 'and';
            $timestamp = $map_filters->timestamp ? $map_filters->timestamp : 'M';
        }

        $vessels = Vessel::select(
            'vessels.id',
            'vessels.name',
            'vessels.imo',
            'vessels.ais_lat',
            'vessels.ais_long',
            'vessels.ais_heading',
            'vessels.ais_nav_status_id',
            'vessels.vessel_type_id'
        )
            ->with('type:id,ais_category_id')
            ->whereNotNull('ais_lat')
            ->whereNotNull('ais_long')
            ->whereNotNull('ais_nav_status_id')
            ->where('company_id', '<>', 352); // not to show zzz-sold vessels

        // $vessels = Vessel::
        //     select('vessels.id',
        //              'vessels.name',
        //              'vessels.imo',
        //              'vessels.vessel_type_id',
        //              'vap.lat',
        //              'vap.lon',
        //              'vap.heading',
        //              'vap.status')
        //     ->with('type:id,ais_category_id')
        //     ->where('vessels.company_id', '<>', 352)
        //     ->leftJoin('select lat,
        //                 lon,
        //                 heading,
        //                 status 
        //                 from vessel_ais_positions orderby timestamp desc as vap', 'vessels.id', '=', 'vap.vessel_id')
        //     ->whereNotNull('vap.lat')
        //     ->whereNotNull('vap.lon')
        //     ->whereNotNull('vap.status')
        //     ->groupBy('vap.vessel_id')
        //     ->get();

        foreach($vessels as $vessel)
        {
            if($vessel->id == 1) {
                $aa = $vessel;
            }
        }
        // calculate limit timestamp to filter the vessels out
        $now = Carbon::now();
        $timestamp = $timestamp == 'D'
            ? $now->subDay()
            : ($timestamp == 'W'? $now->subWeek()
            : ($timestamp == 'M' ? $now->subMonth()
            : NULL));

        if ($timestamp) {
            $vessels = $vessels->where('ais_timestamp', '>', $timestamp);
        }

        if (count($fleets)) {
            $vessels->whereHas('fleets', function ($q) use ($fleets) {
                $q->whereIn('fleets.id', $fleets);
            });
        }

        if (count($networks)) {
            $vessels->join('companies', 'vessels.company_id', '=', 'companies.id')->whereHas('networks', function ($q) use ($networks) {
                $q->whereIn('networks.id', $networks);
            });
        }

        if (count($smff_selected)) {
            if ($smff_operator == "or") {
                $vessels = $vessels
                    ->distinct()
                    ->leftJoin('capabilities AS us', 'vessels.smff_service_id', '=', 'us.id')
                    ->leftJoin('capabilities_values AS cv', 'us.id', '=', 'cv.capabilities_id')
                    ->leftJoin('capabilities_fields AS cf', 'cf.id', '=', 'cv.field_id')
                    ->whereIn('cf.code', $smff_selected)
                    ->where('cv.value', 1);
            } else {
                $count = 1;
                foreach ($smff_selected as $service) {
                    $c = 'capabilities' . $count;
                    $cv = 'capabilities_values' . $count;
                    $cf = 'capabilities_fields' . $count;
                    $vessels
                        ->join('capabilities AS ' . $c, 'vessels.smff_service_id', '=', $c . '.id')
                        ->join('capabilities_values AS ' . $cv, $c . '.id', '=', $cv . '.capabilities_id')
                        ->join('capabilities_fields AS ' . $cf, $cf . '.id', '=', $cv . '.field_id')
                        ->where([
                            $cf . '.code' => $service,
                            $cv . '.value' => 1
                        ]);
                    $count++;
                }
            }
        }

        return $vessels->with('navStatus:value')->get();
    }

    public function getMapVessels($filters)
    {
        $vessels = $this->getFilteredVessels($filters);
        $data = [];
        foreach ($vessels as $vessel) {
            if ($vessel->ais_nav_status_id !== 0) {
                $vessel->ais_heading = 0;
            }
            $data[] = [
                $vessel->id,
                round($vessel->ais_lat, 5),
                round($vessel->ais_long, 5),
                round($vessel->ais_heading, 5),
                $vessel->ais_nav_status_id,
                $vessel->type->ais_category_id,
            ];
        }

        return $data;
    }

    // FIXME: delete this function afterwards
    public function getMapVesselTooltipInfo($id)
    {
        return Vessel::where('id', $id)->select('id', 'imo', 'name', 'latitude', 'longitude', 'speed', 'heading', 'course', 'destination', 'ais_timestamp', 'eta', 'ais_nav_status_id', 'vessel_type_id', 'company_id')
            ->with('navStatus:status_id,value', 'type:id,name,ais_category_id', 'zone:id,name')->first();
    }

    public function getMapVesselTrackTooltipInfo($id)
    {
        $track = VesselHistoricalTrack::find($id);

        $vessel = $track->vessel()->first();
        return [
            'id' => $track->id,
            'vessel_id' => $vessel->id,
            'latitude' => $track->latitude,
            'longitude' => $track->longitude,
            'speed' => $track->speed ?? 'Unknown',
            'heading' => $track->heading ?? 'Unknown',
            'course' => $track->course ?? 'Unknown',
            'ais_timestamp' => $track->ais_timestamp,
            'ais_status' => $track->navStatus->value,
            'type' => $vessel->type ? $vessel->type->ais_category_id  : null
        ];
    }

    public function getMapVessel($id)
    {
        return VesselMapInfoResource::collection(Vessel::where('id', $id)->get())[0];
    }

    public function getMapFleets()
    {
        return Fleet::select('id', 'name', 'code')->get();
    }

    public function getCapabilitiesValues() {
        return Capability::primaryServiceAvailable();
    }

    public function getMapNetworks()
    {
        return Network::select('id', 'name', 'code')->get();
    }

    public function getMapZones()
    {
        return MapGeoLayerResource::collection(MapGeoLayer::select('id', 'name', 'code', 'url_geojson')->whereNotNull('url_geojson')->get());
    }

    public static function getFilteredCompanies($filters)
    {
        $map_filters = json_decode($filters);
        $networks = $map_filters->networks;
        $smff_selected = $map_filters->smff_selected;
        $smff_operator = $map_filters->smff_operator;

        $addresses = CompanyAddress::whereNotNull('latitude')->whereNotNull('longitude')->where([['latitude', '<>', 0], ['longitude', '<>', 0], ['latitude', '<>', ''], ['longitude', '<>', '']]);
        if (count($networks)) {
            $addresses = $addresses->whereHas('company', function ($q) use ($networks) {
                $q->whereHas('networks', function ($q) use ($networks) {
                    $q->whereIn('networks.id', $networks);
                })
                ->leftjoin('capabilities AS cs', function($join) {
                            $join->on('companies.smff_service_id','=','cs.id');
                            $join->on('cs.status','=',DB::raw('1'));
                        });
            });
        }
        $addresses = $addresses->distinct()
            ->select(DB::raw(Company::FIELDS_ADDRESS))
            ->leftJoin('companies AS c', 'c.id', '=', 'company_addresses.company_id')
            ->leftjoin('capabilities AS cs', function($join) {
                        $join->on('c.smff_service_id','=','cs.id');
                        $join->on('cs.status','=',DB::raw('1'));
                    });
        if (count($smff_selected)) {
            if ($smff_operator == "or") {
                $addresses = $addresses
                    ->leftJoin('capabilities_values AS cv', 'cs.id', '=', 'cv.capabilities_id')
                    ->leftJoin('capabilities_fields AS cf', 'cf.id', '=', 'cv.field_id')
                    ->whereIn('cf.code', $smff_selected)
                    ->where('cv.value', 1);
            } else {
                $addresses = $addresses->whereHas('company', function ($company) use ($smff_selected, $smff_operator) {
                    $count = 1;
                    foreach ($smff_selected as $service) {
                        $c = 'capabilities' . $count;
                        $cv = 'capabilities_values' . $count;
                        $cf = 'capabilities_fields' . $count;
                        $company
                            ->join('capabilities AS ' . $c, 'companies.smff_service_id', '=', $c . '.id')
                            ->join('capabilities_values AS ' . $cv, $c . '.id', '=', $cv . '.capabilities_id')
                            ->join('capabilities_fields AS ' . $cf, $cf . '.id', '=', $cv . '.field_id')
                            ->where([
                                $cf . '.code' => $service,
                                $cv . '.value' => 1
                            ]);
                        $count++;
                    }
                });
            }
        }
        DB::enableQueryLog();
        return $addresses->get();
    }

    public function getMapCompanies($filters)
    {
        $companyAddresses = $this->getFilteredCompanies($filters);
        // return $companyAddresses;
        return CompanyAddressMapResource::collection($companyAddresses);
    }

    public static function getFilteredIndividuals($filters, $withAddition = false)
    {
        $map_filters = json_decode($filters);
        $networks = $map_filters->networks;
        $smff_selected = $map_filters->smff_selected;
        $smff_operator = $map_filters->smff_operator;

        $userTable = new User;

        $addresses = $withAddition
            ? $userTable->select(DB::raw("users.*, a.latitude as latitude, a.longitude as longitude, a.id AS address_id, a.zone_id as zone_id, a.street as street, a.city as city, a.state as state, a.country as country, us.primary_service as primary_service"))
            : $userTable->select(DB::raw("users.*, a.latitude as latitude, a.longitude as longitude, a.id AS address_id, a.zone_id as zone_id, us.primary_service as primary_service"));
        $addresses = $addresses
                ->leftJoin('companies_users AS cu', 'users.id', '=', 'cu.user_id')
                ->leftJoin('companies', 'companies.id', '=', 'cu.company_id')
                ->leftJoin('user_address AS a', 'a.user_id', '=', 'users.id')
                ->leftJoin('capabilities AS us', 'users.smff_service_id', '=', 'us.id')
                ->whereNotNull('latitude')->whereNotNull('longitude');
        if (count($networks)) {
            $addresses
                ->whereHas('networks', function ($q) use ($networks) {
                    $q->whereIn('networks.id', $networks);
                });
        }
        if (count($smff_selected)) {
            if ($smff_operator == "or") {
                $addresses = $addresses
                    ->distinct()
                    ->leftJoin('capabilities_values AS cv', 'us.id', '=', 'cv.capabilities_id')
                    ->leftJoin('capabilities_fields AS cf', 'cf.id', '=', 'cv.field_id')
                    ->whereIn('cf.code', $smff_selected)
                    ->where('cv.value', 1);
            } else {
                $addresses = $withAddition
                    ? $userTable->select(DB::raw("users.*, a.latitude as latitude, a.longitude as longitude, a.id AS address_id, a.zone_id as zone_id, a.street as street, a.city as city, a.state as state, a.country as country, us.primary_service as primary_service"))
                    : $userTable->select(DB::raw("users.*, a.latitude as latitude, a.longitude as longitude, a.id AS address_id, a.zone_id as zone_id, us.primary_service as primary_service"));
                $addresses = $addresses
                    ->leftJoin('user_address AS a', 'a.user_id', '=', 'users.id')
                    ->leftJoin('companies', 'users.primary_company_id', '=', 'companies.id')
                    ->leftJoin('capabilities AS us', 'users.smff_service_id', '=', 'us.id')
                    ->whereNotNull('latitude')->whereNotNull('longitude');
                $count = 1;
                foreach ($smff_selected as $service) {
                    $c = 'capabilities' . $count;
                    $cv = 'capabilities_values' . $count;
                    $cf = 'capabilities_fields' . $count;
                    $addresses
                        ->join('capabilities AS ' . $c, 'users.smff_service_id', '=', $c . '.id')
                        ->join('capabilities_values AS ' . $cv, $c . '.id', '=', $cv . '.capabilities_id')
                        ->join('capabilities_fields AS ' . $cf, $cf . '.id', '=', $cv . '.field_id')
                        ->where([
                            $cf . '.code' => $service,
                            $cv . '.value' => 1
                        ]);
                    $count++;
                }
            }
        }

        return $addresses->get();
    }

    public function getMapIndividuals($filters)
    {
        $individuals = $this->getFilteredIndividuals($filters);
        return UserAddressMapResource::collection($individuals);
    }

    public function getMapSMFF($id)
    {
        return Capability::where('id', $id)->first();
    }

    public function getMapCompany($id)
    {
        return CompanyMapInfoResource::collection(Company::where('id', $id)->get())[0];
    }

    public function getMapCompanyAddress($id)
    {
        $address = CompanyAddress::where('id', $id);
        return $address ? CompanyAddressMapInfoResource::collection(CompanyAddress::where('id', $id)->get())[0] : null;
    }

    public function getMapUser($id)
    {
        return UserMapInfoResource::collection(User::where('id', $id)->get())[0];
    }

    public function getMapUserAddress($id)
    {
        $address = UserAddress::where('id', $id);
        return $address ? UserAddressMapInfoResource::collection($address->get())[0] : null;
    }

    public function searchMap($search)
    {
        $data = [];
        //test if the data is coordinates
        $geo = explode(',', $search);
        if (count($geo) === 2 && $this->validateLatLong($geo[0], $geo[1])) {
            $data[] = [
                'name' => $search,
                'description' => 'Location on map',
                'info' => [
                    'id' => 0,
                    'type' => 'location',
                    'latitude' => $geo[0],
                    'longitude' => $geo[1]
                ]
            ];
        } else {
            //search for vessels matching the search string
            $vessels = Vessel::search($search)->get()->load(['type']);
            foreach ($vessels as $vessel) {
                $vesselPosition = $vessel->aisPositions()->latest('updated_at')->first();
                if($vesselPosition) {
                    if ($vesselPosition->lat && $vesselPosition->lon && $vesselPosition->status !== null) {
                        $imo = $vessel->imo ?? '--';
                        $data[] = [
                            'name' => $vessel->name . ' - [' . $imo . ']',
                            'description' => $vessel->type->name,
                            'info' => [
                                'id' => $vessel->id,
                                'address_id' => 0,
                                'type' => 'vessel',
                                'latitude' => $vesselPosition->lat,
                                'longitude' => $vesselPosition->lon
                            ]
                        ];
                    }
                }
            }
           //search for company addresses matching the search string
            $company_addresses = CompanyAddress::search($search)->get()->load('company.networks', 'company.smffCapability');
            foreach ($company_addresses as $company_address) {
                if ($company_address->latitude && $company_address->longitude && $company_address->company && $company_address->company->smffCapability && count($company_address->company->networks)) {
                    $plan_number = $company_address->company->plan_number ?? '--';
                    $data[] = [
                        'name' => $company_address->company->name . ' - [' . $plan_number . ']',
                        'description' => $company_address->city . ', ' . $company_address->country,
                        'info' => [
                            'id' => $company_address->company->id,
                            'address_id' => $company_address->id,
                            'type' => 'company',
                            'latitude' => $company_address->latitude,
                            'longitude' => $company_address->longitude
                        ]
                    ];
                }
            }

            //search for individual addresses matching the search string
            $individual_addresses = UserAddress::search($search)->get()->load('user.cnetworks', 'user.smffCapability');
            foreach ($individual_addresses as $individual_address) {
                if ($individual_address->latitude && $individual_address->longitude && $individual_address->user && $individual_address->user->smffCapability && count($individual_address->user->networks)) {
                    $data[] = [
                        'name' => $individual_address->user->first_name . ' ' . $individual_address->user->last_name,
                        'description' => $individual_address->city . ', ' . $individual_address->country,
                        'info' => [
                            'id' => $individual_address->user->id,
                            'address_id' => $individual_address->id,
                            'type' => 'individual',
                            'latitude' => $individual_address->latitude,
                            'longitude' => $individual_address->longitude
                        ]
                    ];
                }
            }
        }
        return response()->json($data);
    }

    public function getZoneTestJson()
    {
        return response()->json([
            'data' => json_decode(file_get_contents(base_path() . '/storage/zone_tests/index.json'))]
        );
    }

    /**
     * Validates a given coordinate
     *
     * @param float|int|string $lat Latitude
     * @param float|int|string $long Longitude
     * @return bool `true` if the coordinate is valid, `false` if not
     */
    private function validateLatLong($lat, $long)
    {
        return preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $lat . ',' . $long);
    }
}
