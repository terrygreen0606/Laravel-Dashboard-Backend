<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VesselAisApiCost;
use App\Models\VesselAisSettings;
use App\Models\Vessel;
use App\Http\Resources\VesselPollResource;
use App\Http\Resources\VesselTrackResource;
use App\Helpers\MTHelper;
class AISController extends Controller
{

    public function getAISData($imo,Request $request)
    {
        $extended = $request->get("extended") ? true : false;
        $satellite = $request->get("satellite") ? true : false;
        $result = MTHelper::getVesselAIS($imo, $extended, $satellite, false);
        return response()->json($result);
    }

    public function getAISPhoto($imo,Request $request)
    {
        $result = MTHelper::getVesselPhoto($imo, false);
        return response()->json($result);
    }

    public function getAISParticulars($imo,Request $request)
    {
        $result = MTHelper::getVesselPartculars($imo, false);
        return response()->json($result);
    }

    public function getAISTrack($imo, Request $request)
    {
        $settings = VesselAisSettings::first();

        $extended = $request->get("extended") ? true : false;
        $satellite = $request->get("satellite") ? true : false;
        $hourly = $request->get("hourly") ? true : ($settings->historical_track_period_all == "hourly");
        $days = $request->get("days") ? intval($request->get("days")) : $settings->historical_track_days_all;



        $result = MTHelper::getVesselTrack($imo, $hourly, $days,
            $extended, $satellite, false);

        if ($result['success']) {
            return VesselTrackResource::collection($result['track']);
        } else {
            return response()->json($result);
        }
    }

    public function getAISTrackTest($imo)
    {
         $settings = VesselAisSettings::first();

        $extended = false;
        $satellite = true ;
        $hourly = false;
        $days = 5;



        $result = MTHelper::getVesselTrack($imo, $hourly, $days,
            $extended, $satellite, false);

        if ($result['success']) {
            return VesselTrackResource::collection($result['track']);
        } else {
            return response()->json($result);
        }
    }

    public function getTrackData($id)
    {
        $result = MTHelper::getVesselTrackData($id);

        if ($result['success']) {
            return VesselTrackResource::collection($result['track']);
        } else {
            return response()->json($result);
        }
    }

    public function costVesselsAISPoll(Request $request)
    {
        $user = Auth::user();
        $type = $request->get("type");
        $vessel_ids = $request->get("vessel_ids");
        $network_ids = $request->get("network_ids");
        $fleet_ids = $request->get("fleet_ids");
        $repeating = $request->get("repeating") ? true : false;
        $repeating_interval = $request->get("repeating_interval");

        // convenience flags
        $satellite = false;
        switch ($type) {
        case VesselAisApiCost::POS_SAT_SIMPLE  :
        case VesselAisApiCost::POS_SAT_EXTENDED :
        case VesselAisApiCost::TRACK_SAT_SIMPLE  :
        case VesselAisApiCost::TRACK_SAT_EXTENDED :
          $satellite = true;
          break;
        }

        if ($repeating && intval($repeating_interval) < 2) {
            $settings = VesselAisSettings::first();
            if ($satellite) {
                $repeating_interval = $settings->satellite_positions_interval_batch;
                if (!empty($vessel_ids) && count($vessel_ids) == 1) {
                    $repeating_interval = $settings->satellite_positions_interval_single;
                }
            } else {
                $repeating_interval = $settings->terrestrial_positions_interval_batch;
                if (!empty($vessel_ids) && count($vessel_ids) == 1) {
                    $repeating_interval = $settings->terrestrial_positions_interval_single;
                }
            }
        }

        $type = VesselAisApiCost::find($type);

        if (empty($type)) {
            return response()->json(['success' => false, 'message' => 'type not found']);
        }

        if (!empty($vessel_ids)) {
            $vessel_count = count($vessel_ids);
        } else if (!empty($network_ids)) {
            $network_vessels = Vessel::
                select(['vessels.*'])
                ->distinct()
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->whereIn('nc.network_id', $network_ids)
                ->get();
            $vessel_count = count($network_vessels);
        } else if (!empty($fleet_ids)) {
            $fleet_vessels = Vessel::
              select(['vessels.*'])
              ->distinct()
              ->join('vessels_fleets AS vf', 'vessels.id', '=', 'vf.vessel_id')
              ->whereIn('vf.fleet_id', $fleet_ids)
              ->get();
            $vessel_count = count($fleet_vessels);
        }

        $cost = $vessel_count * $type->cost_per_unit;

        if ($repeating) {
            $per_hour = 60 / $repeating_interval;
            $cost = $cost * $per_hour;
        }

        return response()->json(['success' => true, 'cost' => $cost, 'per_hour' => $repeating, 'vessel_count' => $vessel_count]);
    }
/*
 addVesselPoll($user, $type, $vessel_ids,
    $repeating = false, $repeating_interval = 5,
      $start_date = null, $stop_date = null)
      */
    public function addVesselsAISPoll(Request $request)
    {
        $user = Auth::user();
        $type = $request->get("type");
        $vessel_ids = $request->get("vessel_ids");
        $network_ids = $request->get("network_ids");
        $fleet_ids = $request->get("fleet_ids");
        $repeating = $request->get("repeating") ? true : false;
        $repeating_interval = $request->get("repeating_interval");
        $start_date = $request->get("start_date");
        $stop_date = $request->get("stop_date");
        $adding = $request->get("adding");

        // convenience flags
        $satellite = false;
        switch ($type) {
        case VesselAisApiCost::POS_SAT_SIMPLE  :
        case VesselAisApiCost::POS_SAT_EXTENDED :
        case VesselAisApiCost::TRACK_SAT_SIMPLE  :
        case VesselAisApiCost::TRACK_SAT_EXTENDED :
          $satellite = true;
          break;
        }

        if ($repeating && intval($repeating_interval) < 2) {
            $settings = VesselAisSettings::first();
            if ($satellite) {
                $repeating_interval = $settings->satellite_positions_interval_batch;
                if (!empty($vessel_ids) && count($vessel_ids) == 1) {
                    $repeating_interval = $settings->satellite_positions_interval_single;
                }
            } else {
                $repeating_interval = $settings->terrestrial_positions_interval_batch;
                if (!empty($vessel_ids) && count($vessel_ids) == 1) {
                    $repeating_interval = $settings->terrestrial_positions_interval_single;
                }
            }
        }

        $result = 'send something!!!';
        if (!empty($vessel_ids)) {
            if (count($vessel_ids) == 1) {
                $polls = MTHelper::getVesselPolls($vessel_ids[0]);
                foreach ($polls as $poll) {
                    MTHelper::removePoll($poll->id);
                }
                $result = ['success' => true, 'message' => 'Poll Deleted'];
            }
            if ($adding) {
                $result = MTHelper::addVesselPoll($user, $type, $vessel_ids,
                $repeating, $repeating_interval);
            }
        } else if (!empty($network_ids)) {
            if (count($network_ids) == 1) {
                $polls = MTHelper::getNetworkPolls($network_ids[0]);
                foreach ($polls as $poll) {
                    MTHelper::removePoll($poll->id);
                }
            }
            if ($adding) {
                $result = MTHelper::addNetworkPoll($user, $type, $network_ids,
                $repeating, $repeating_interval);
            }
        } else if (!empty($fleet_ids)) {
            if (count($fleet_ids) == 1) {
                $polls = MTHelper::getFleetPolls($fleet_ids[0]);
                foreach ($polls as $poll) {
                    MTHelper::removePoll($poll->id);
                }
            }
            if ($adding) {
                $result = MTHelper::addFleetPoll($user, $type, $fleet_ids,
                $repeating, $repeating_interval);
            }
        }

        return response()->json($result);
    }

    public function stopAISPoll($id, Request $request)
    {
        $result = MTHelper::removePoll($id);
        return response()->json($result);
    }

    public function getVesselsAISPoll($id)
    {
        $polls = MTHelper::getVesselPolls($id);

        return VesselPollResource::collection($polls);
    }

    public function getNetworkAISPoll($id)
    {
        $polls = MTHelper::getNetworkPolls($id);

        return VesselPollResource::collection($polls);
    }


    public function getFleetAISPoll($id)
    {
        $polls = MTHelper::getFleetPolls($id);

        return VesselPollResource::collection($polls);
    }

    public function getVesselsAISPollByType($id, $type)
    {
        $polls = MTHelper::getVesselPolls($id, $type);

        return VesselPollResource::collection($polls);
    }

    public function getNetworkAISPollByType($id, $type)
    {
        $polls = MTHelper::getNetworkPolls($id, $type);

        return VesselPollResource::collection($polls);
    }

    public function getFleetAISPollByType($id, $type)
    {
        $polls = MTHelper::getFleetPolls($id, $type);

        return VesselPollResource::collection($polls);
    }


    public function getAllVesselsWithPolls()
    {
        $polls = MTHelper::getAllVesselsWithPolls();

        return response()->json($polls);
    }

    public function getSettings() {
        $settings = VesselAisSettings::first();

        return response()->json($settings->attributesToArray());
    }

    public function saveSettings(Request $request) {
        $settings = VesselAisSettings::first();

        $update = $settings->update($request->all());

        return response()->json([
            'success' => $update,
            'message' => (($update) ? "Settings saved." : "Error saving settings")
        ]);
    }

}
