<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Company;
use App\Models\Network;
use App\Models\Fleet;
use App\Models\Vessel;
use App\Models\AisStatus;
use App\Models\VesselAisApiCost;
use App\Models\VesselAisSettings;
use App\Models\VesselAISPositions;
use App\Models\VesselAISDetails;
use App\Models\VesselAisPoll;
use App\Models\VesselAISMTPoll;
use App\Helpers\GeoHelper;
use App\Helpers\MT_API_Helper;
use App\Models\NavStatus;
use Carbon\Carbon;
use DateTime;

class AISMTController extends Controller
{
    public function __construct(MT_API_Helper $mt_api_helper)
    {
        $this->mt_api_helper = $mt_api_helper;
    }
    // Get The Single Vessel AIS Data
    public function getAIS_PS07_Single(Request $request)
    {
        $data = $request->parametersPS07;
        $aisPosition = MT_API_Helper::getAIS_PS07($data['id'], $data['satellite'], $data['extended']);
        return response()->json($aisPosition);
    }

    // Get The Networks Or Fleets AIS Data
    public function getAIS_PS07_Bulk(Request $request)
    {
        $nowTime = date("Y-m-d H:i:s");
        if(request('last_update') && request('last_update') != "null") {
            $nowTime = request('last_update');
        }
        if($request->network) {
            // network vessel content
            $vessels = Vessel::select(['vessels.*'])
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->where('nc.network_id', $request->id)
                ->where('vessels.ais_timestamp', '<', $nowTime)
                ->get();
        } else {
            // fleet vessel content
            $vessels = Vessel::select(['vessels.*'])
                ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
                ->where('vf.fleet_id', $request->id)
                ->where('vessels.ais_timestamp', '<', $nowTime)
                ->get();
        }

        $vessel_count = count($vessels);

        if($vessel_count > 0) {
            $aisResponse = $this->mt_api_helper->getAIS_PS07Bulk($vessels, $request->satellite, $request->extended);
            $currentTime = date("Y-m-d H:i:s");
            if($request->network) {
                Network::where('id', $request->id)->update(['ais_last_updated_at'=> $currentTime]);
            } else {
                Fleet::where('id', $request->id)->update(['ais_last_updated_at' => $currentTime]);
            }
            
        } else {
            return response()->json(['success' => false, 'message' => 'No Vessel!']);
        }

        return response()->json(['success' => true, 'message' => 'success']);
    }

    // The Single Vessel AIS Continuous
    public function getAIS_PS07_Cont($id, $satellite, $condition, $bulk = false)
    {
        $vessel = Vessel::where('id', $id)->first();
        $current_time = new DateTime();

        $imo = $vessel->imo;
        $settings = VesselAisSettings::first();

        if($imo) {
            if($satellite) {
                $bulk
                    ? $interval = $settings->satellite_positions_interval_batch
                    : $interval = $settings->satellite_positions_interval_single;
            } else {
                $bulk
                    ? $interval = $settings->terrestrial_positions_interval_batch
                    : $interval = $settings->terrestrial_positions_interval_single;
            }
    
            if($condition) {
                $con = VesselAISMTPoll::where('vessel_id', $id)->first();
                if($con) {
                    VesselAISMTPoll::where('vessel_id', $id)->delete();
                }
                $poll_ais_mt_data = [
                    'vessel_id' => $id,
                    'imo' => $imo,
                    'satellite' => $satellite,
                    'extended'  => 0,
                    'interval' => $interval,
                    'last_update' => $current_time,
                ];
                VesselAISMTPoll::create($poll_ais_mt_data);
            } else {
                VesselAISMTPoll::where('vessel_id', $id)->delete();
                return response()->json(['success' => true, 'message' => 'Poll Removed.']);
            }
    
            return response()->json(['success' => true, 'message' => 'Poll Updated.']);
        }
        return response()->json(['success' => false, 'message' => 'Vessel imo does not exist!']);
    }

    // The Networks Or Fleets Bulk AIS Data Continuous
    public function getAIS_PS07_Bulk_Cont(Request $request)
    {
        $bulk = true;
        if($request->network) {
            // network vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->where('nc.network_id', $request->id)
                ->get();
        } else {
            // fleet vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
                ->where('vf.fleet_id', $request->id)
                ->get();
        }

        $vessel_count = count($vessels);
        $bulkResponse = response()->json(['success' => true, 'message' => 'Poll Pending.']);
        if($vessel_count > 0) {
            foreach ($vessels as $vessel) {
                $id = $vessel->id;
                $bulkResponse = $this->getAIS_PS07_Cont($id, $request->satellite, $request->condition, $bulk);
            }
            Network::where('ter_status', 1)->update(['ter_status' => 0]);
            Network::where('sat_status', 1)->update(['sat_status' => 0]);
            Fleet::where('ter_status', 1)->update(['ter_status' => 0]);
            Fleet::where('sat_status', 1)->update(['sat_status' => 0]);
            if ($request->condition) {
                $request->network
                    ? $request->satellite
                        ? Network::where('id', $request->id)->update(['sat_status' => 1])
                        : Network::where('id', $request->id)->update(['ter_status' => 1])
                    : $request->satellite
                        ? Fleet::where('id', $request->id)->update(['sat_status' => 1])
                        : Fleet::where('id', $request->id)->update(['ter_status' => 1]);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'No Vessel!']);
        }

        return $bulkResponse;
    }

    // Get The Single Vessel Historical Track Data
    public function getAIS_PS01_Single(Request $request)
    {
        $aisHistoricalTrack = MT_API_Helper::getAIS_PS01(request('parametersPS07')['id'], request('parametersPS07')['satellite'], request('parametersPS07')['extended']);
        if ($aisHistoricalTrack['success']) {
            return response()->json(['success' => $aisHistoricalTrack['success'], 'historical_track' => $aisHistoricalTrack['payload']]);
        }
        return response()->json(['success' => $aisHistoricalTrack['success'], 'message' => $aisHistoricalTrack['message']]);
    }

    // Get The Networks Or Fleets Vessels Historical Track Data
    public function getAIS_PS01_Bulk($networkorfleet, $network, $satellite, $extended)
    {
        if($networkorfleet) {
            // network vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->where('nc.network_id', $network)
                ->get();
        } else {
            // fleet vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
                ->where('vf.fleet_id', $network)
                ->get();
        }
        
        $vessel_count = count($vessels);

        if($vessel_count > 0) {
            foreach ($vessels as $vessel) {
                $id = $vessel->id;
                // Get every vessel historical track
                MT_API_Helper::getAIS_PS01($id, $satellite, $extended);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'No Vessel!']);
        }

        return response()->json(['success' => true, 'message' => 'success']);
    }

    // Get The Single Vessel AIS Photo
    public function getAIS_VD01_Single($imo)
    {
        $aisPhoto = MT_API_Helper::getAIS_VD01($imo);
        if($aisPhoto['success']) {
            return response()->json([
                'success' => $aisPhoto['success'],
                'message' => $aisPhoto['message'],
            ]);
        } else {
            return response()->json([
                'success' => $aisPhoto['success'],
                'message' => $aisPhoto['message']
            ]);
        }
    }

    // Get The Networks Or Fleets Vessels AIS Photos
    public function getAIS_VD01_Bulk($networkorfleet, $network, $satellite, $extended)
    {
        if($networkorfleet) {
            // network vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->where('nc.network_id', $network)
                ->get();
        } else {
            // fleet vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
                ->where('vf.fleet_id', $network)
                ->get();
        }

        $vessel_count = count($vessels);

        if($vessel_count > 0) {
            foreach ($vessels as $vessel) {
                $imo = $vessel->imo;
                // Get every vessel photo
                MT_API_Helper::getAIS_VD01($imo);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'No Vessel!']);
        }

        return response()->json(['success' => true, 'message' => 'success']);
    }

    // Get The Single Vessel Particular AIS Data
    public function getAIS_VD02_Single($id)
    {
        $aisParticular = MT_API_Helper::getAIS_VD02($id);
        return response()->json($aisParticular);
    }

    // Get The Networks Or Fleets Vessels Particular AIS Data
    public function getAIS_VD02_Bulk($networkorfleet, $network, $satellite, $extended)
    {
        if($networkorfleet) {
            // network vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->where('nc.network_id', $network)
                ->get();
        } else {
            // fleet vessel content
            $vessels = Vessel::
                select(['vessels.*'])
                ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
                ->where('vf.fleet_id', $network)
                ->get();
        }

        $vessel_count = count($vessels);

        if($vessel_count > 0) {
            foreach ($vessels as $vessel) {
                $id = $vessel->id;
                // Get every vessel particular ais data
                MT_API_Helper::getAIS_VD02($id);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'No Vessel!']);
        }

        return response()->json(['success' => true, 'message' => 'success']);
    }

    // Show The AIS Data
    public function showAISData($id)
    {
        $aisHistoricalTrack = null;
        $aisExtendedPosition = VesselAISPositions::where([['vessel_id', $id],['msgtype', 1]])
            ->latest('timestamp')
            ->first();
        $aisSimplePosition = VesselAISPositions::where([['vessel_id', $id], ['msgtype', 2]])->latest('timestamp')->first();
        $lastData = VesselAISPositions::where('vessel_id', $id)->latest('timestamp')->first();
        $vesselCont = VesselAISMTPoll::where('vessel_id', $id)->first();
        $aisParticular = VesselAISDetails::where('vessel_id', $id)->first();
        $lookback = VesselAisSettings::first()->historical_track_days_all;
        if ($lastData) {
            $dayToStart = Carbon::create($lastData->timestamp)->subDays($lookback);
            $aisHistoricalTrack = VesselAISPositions::where([
                ['timestamp', '>=', $dayToStart],
                ['vessel_id', $id],
            ])
                ->oldest('timestamp')
                ->get();
        }

        $network_vessels = Vessel::select(['vessels.*'])
            ->distinct()
            ->join("companies AS c1", 'vessels.company_id','=','c1.id')
            ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
            ->where('c1.networks_active', 1)
            ->where('vessels.id', $id)
            ->get();
        $isNetwork = count($network_vessels);
        $fleet_vessels = Vessel::select(['vessels.*'])
            ->distinct()
            ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
            ->where('vessels.id', $id)
            ->get();
        $isFleet = count($fleet_vessels);
        $cont = [
            'active' => 0,
            'satellite' => 0,
        ];
        if($vesselCont) {
            $satellite = 0;
            if($vesselCont->satellite) {
                $satellite = 1;
            }
            $cont = [
                'active' => 1,
                'satellite' => $satellite,
            ];
        }

        if ($aisExtendedPosition) {
            $navStatusValue = NavStatus::where('status_id', $aisExtendedPosition->status)->first()->value;
            if ($aisSimplePosition) {
                if($lastData == $aisSimplePosition) {
                    $navStatusValue = NavStatus::where('status_id', $aisSimplePosition->status)->first()->value;
                    $aisExtendedPosition->mmsi = $aisSimplePosition->mmsi;
                    $aisExtendedPosition->lat = $aisSimplePosition->lat;
                    $aisExtendedPosition->lon = $aisSimplePosition->lon;
                    $aisExtendedPosition->speed = $aisSimplePosition->speed;
                    $aisExtendedPosition->heading = $aisSimplePosition->heading;
                    $aisExtendedPosition->course = $aisSimplePosition->course;
                    $aisExtendedPosition->status = $aisSimplePosition->status;
                    $aisExtendedPosition->timestamp = $aisSimplePosition->timestamp;
                    $aisExtendedPosition->dsrc = $aisSimplePosition->dsrc;
                    $aisExtendedPosition->msgtype = $aisSimplePosition->msgtype;
                    $aisExtendedPosition->zone_id = $aisSimplePosition->zone_id;
                }
            }
            $aisExtendedPosition->nav_status_value = $navStatusValue;
            $response = [
                'success' => true,
                'message' => 'success',
                'position' => $aisExtendedPosition,
                'contActive' => $cont,
                'isNetwork' => $isNetwork,
                'isFleet' => $isFleet
            ];
        } else if ($aisSimplePosition) {
            $navStatusValue = NavStatus::where('status_id', $aisSimplePosition->status)->first()->value;
            $aisSimplePosition->nav_status_value = $navStatusValue;
            $response = [
                'success' => true,
                'message' => 'success',
                'position' => $aisSimplePosition,
                'contActive' => $cont,
                'isNetwork' => $isNetwork,
                'isFleet' => $isFleet
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'success',
                'contActive' => $cont,
                'isNetwork' => $isNetwork,
                'isFleet' => $isFleet
            ];
        }

        if ($aisParticular) {
            $response['particular'] = $aisParticular;
        }

        if ($aisHistoricalTrack) {
            $response['historical_track'] = $aisHistoricalTrack;
        }

        $response['vessel_type'] = Vessel::find($id)->type->ais_category_id;

        return response()->json($response);
    }

    // Calculate The Cost For AIS Poll
    public function costVesselsAISPoll(Request $request)
    {
        $type = $request->get("type");
        $vessel_ids = $request->get("vessel_ids");
        $network_ids = $request->get("network_ids");
        $fleet_ids = $request->get("fleet_ids");
        $repeating = $request->get("repeating") ? true : false;
        $repeating_interval = $request->get("repeating_interval");

        $result = MT_API_Helper::costVesselsAISPoll($type, $vessel_ids, $network_ids, $fleet_ids, $repeating, $repeating_interval);
        return $result;
    }

    // Get the Settings
    public function getSettings()
    {
        $settings = VesselAisSettings::first();
        return response()->json($settings->attributesToArray());
    }

    // Save the Settings
    public function saveSettings(Request $request)
    {
        $settings = VesselAisSettings::first();
        $update = $settings->update($request->all());

        return response()->json([
            'success' => $update,
            'message' => (($update) ? "Settings saved." : "Error saving settings")
        ]);
    }

    public function getAllVesselsWithPolls()
    {
        $response = MT_API_Helper::getAllVesselsWithPolls();
        return response()->json($response);
    }

    // Checking the Credit
    public function checkingCredit()
    {
        $apiUrl = 'https://services.marinetraffic.com/api/exportcredits/1297a75dfc3dc2348afdd49c50a80b0170f04f7a/';
        $results = file_get_contents($apiUrl);
        $resData = simplexml_load_string($results);
        return response()->json(['success' => 'Success', 'data' => $resData->CREDITS->attributes()->CREDIT_BALANCE]);
    }

}
