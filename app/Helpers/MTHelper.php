<?php

namespace App\Helpers;

use App\Models\AisStatus;
use App\Models\Vessel;
use App\Models\VesselHistoricalTrack;
use App\Models\Port;
use App\Models\VesselAisPoll;
use App\Models\VesselAisApiCost;
use App\Models\VesselAISPositions;
use App\Models\VesselAisSettings;
use Intervention\Image\ImageManagerStatic as Image;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MTHelper
{
    const SANDBOX = false;

    const DUMMY_PARICULARS = '{"METADATA":{"TOTAL_RESULTS":1,"TOTAL_PAGES":1,"CURRENT_PAGE":1},"DATA":[{"MMSI":"544123120","IMO":"9085388","NAME":"SUPER STAR","PLACE_OF_BUILD":"","BUILD":"1995","BREADTH_EXTREME":"32.2","SUMMER_DWT":"45999","DISPLACEMENT_SUMMER":"55811","CALLSIGN":"C2AY2","FLAG":"NR","DRAUGHT":"12.06","LENGTH_OVERALL":"183.2","FUEL_CONSUMPTION":"29 t\/day at 14.00 kn","SPEED_MAX":"","SPEED_SERVICE":"15.3","LIQUID_OIL":"52719","OWNER":"OCEAN GROW INTERNATIONAL SHIPMANAGEMENT","MANAGER":"WINSON SHIPPING","MANAGER_OWNER":"","VESSEL_TYPE":"OIL\/CHEMICAL TANKER"}]}';
    const DUMMY_PHOTO = '[{"URL":"https:\/\/photos.marinetraffic.com\/ais\/showphoto.aspx?photoid=29709"}]';
    const DUMMY_POS_SIMPLE = '[{"MMSI":"244170000","LAT":"50.287190","LON":"-44.098710","SPEED":"116","HEADING":"33","COURSE":"29","STATUS":"0","TIMESTAMP":"2020-04-13T15:44:31","DSRC":"SAT"}]';
    const DUMMY_POS_EXTENDED = '[{"MMSI":"244170000","LAT":"50.287190","LON":"-44.098710","SPEED":"116","HEADING":"33","COURSE":"29","STATUS":"0","TIMESTAMP":"2020-04-13T15:44:31","SHIPNAME":"JAN VAN GENT","SHIPTYPE":"70","TYPE_NAME":"General Cargo","AIS_TYPE_SUMMARY":"Cargo","IMO":"9456721","CALLSIGN":"PBUF","FLAG":"NL","PORT_ID":"","PORT_UNLOCODE":"","CURRENT_PORT":"","LAST_PORT_ID":"213","LAST_PORT_UNLOCODE":"USMSY","LAST_PORT":"NEW ORLEANS","LAST_PORT_TIME":"2020-03-31T13:19:00","DESTINATION":"ISGRT","ETA":"2020-04-17T07:00:00","ETA_CALC":"2020-04-17T06:46:00","LENGTH":"142.95","WIDTH":"18.9","DRAUGHT":"76","GRT":"8999","NEXT_PORT_ID":"19879","NEXT_PORT_UNLOCODE":"ISGRT","NEXT_PORT_NAME":"GRUNDARTANGI","NEXT_PORT_COUNTRY":"IS","DWT":"12007","YEAR_BUILT":"2009","DSRC":"SAT"}]';
    const DUMMY_TRACK_SIMPLE = '[{"MMSI":"636015050","STATUS":"0","SPEED":"111","LON":"-88.077590","LAT":"26.691060","COURSE":"13","HEADING":"3","TIMESTAMP":"2020-04-07T08:59:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"119","LON":"-87.722600","LAT":"29.767940","COURSE":"327","HEADING":"325","TIMESTAMP":"2020-04-08T00:29:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"28","LON":"-88.042200","LAT":"30.723160","COURSE":"174","HEADING":"353","TIMESTAMP":"2020-04-09T06:08:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"57","LON":"-88.031090","LAT":"30.662410","COURSE":"184","HEADING":"182","TIMESTAMP":"2020-04-10T00:37:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.099860","LAT":"30.080280","COURSE":"345","HEADING":"3","TIMESTAMP":"2020-04-11T00:01:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.101860","LAT":"30.083420","COURSE":"353","HEADING":"95","TIMESTAMP":"2020-04-12T00:30:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"3","LON":"-88.099040","LAT":"30.086620","COURSE":"220","HEADING":"181","TIMESTAMP":"2020-04-13T00:55:00","SHIP_ID":"755185"}]';
    const DUMMY_TRACK_EXTENDED = '[{"MMSI":"636015050","STATUS":"0","SPEED":"111","LON":"-88.077590","LAT":"26.691060","COURSE":"13","HEADING":"3","TIMESTAMP":"2020-04-07T08:59:00","SHIP_ID":"755185","WIND_ANGLE":"131","WIND_SPEED":"9","WIND_TEMP":"24"},{"MMSI":"636015050","STATUS":"0","SPEED":"119","LON":"-87.722600","LAT":"29.767940","COURSE":"327","HEADING":"325","TIMESTAMP":"2020-04-08T00:29:00","SHIP_ID":"755185","WIND_ANGLE":"206","WIND_SPEED":"10","WIND_TEMP":"23"},{"MMSI":"636015050","STATUS":"0","SPEED":"28","LON":"-88.042200","LAT":"30.723160","COURSE":"174","HEADING":"353","TIMESTAMP":"2020-04-09T06:08:00","SHIP_ID":"755185","WIND_ANGLE":"","WIND_SPEED":"","WIND_TEMP":""},{"MMSI":"636015050","STATUS":"0","SPEED":"57","LON":"-88.031090","LAT":"30.662410","COURSE":"184","HEADING":"182","TIMESTAMP":"2020-04-10T00:37:00","SHIP_ID":"755185","WIND_ANGLE":"","WIND_SPEED":"","WIND_TEMP":""},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.099860","LAT":"30.080280","COURSE":"345","HEADING":"3","TIMESTAMP":"2020-04-11T00:01:00","SHIP_ID":"755185","WIND_ANGLE":"18","WIND_SPEED":"14","WIND_TEMP":"21"},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.101860","LAT":"30.083420","COURSE":"353","HEADING":"95","TIMESTAMP":"2020-04-12T00:30:00","SHIP_ID":"755185","WIND_ANGLE":"121","WIND_SPEED":"16","WIND_TEMP":"21"},{"MMSI":"636015050","STATUS":"0","SPEED":"3","LON":"-88.099040","LAT":"30.086620","COURSE":"220","HEADING":"181","TIMESTAMP":"2020-04-13T00:55:00","SHIP_ID":"755185","WIND_ANGLE":"189","WIND_SPEED":"29","WIND_TEMP":"24"}]';

    public static function addVesselPoll(
        $user,
        $type,
        $vessel_ids,
        $repeating = false,
        $repeating_interval = 5,
        $start_date = null,
        $stop_date = null
    ) {
        $poll = self::createPoll(
            $user,
            $type,
            $repeating,
            $repeating_interval,
            $start_date,
            $stop_date
        );

        $poll->vessels()->sync($vessel_ids);

        $success = true;
        return [
            'success' => $success,
            'message' => 'Poll Updated.',
            'poll_id' => $poll->id,
        ];
    }

    public static function removePoll($id)
    {
        $poll = VesselAisPoll::find($id);
        $poll->status = 4;
        $poll->save();
    }

    public static function getVesselPolls($id, $type = false)
    {
        $polls = VesselAisPoll::where('repeating', 1)
            ->where('status', '<>', 4)
            ->whereHas('vessels', function ($q) use ($id) {
                $q->where('vessels.id', $id);
            });

        if ($type) {
            $polls = $polls->where('type', $type);
        }

        return $polls->get();
    }

    public static function getAllVesselsWithPolls($type = false)
    {
        $polls = VesselAisPoll::where('repeating', 1)
            ->where([['status', '<>', 4]])
            ->whereHas('vessels');

        if ($type) {
            $polls = $polls->where('type', $type);
        }

        $polls = $polls->get();

        $vessels = [];
        foreach ($polls as $poll) {
            $poll_vessels = $poll->vessels()->get();
            foreach ($poll_vessels as $vessel) {
                $vessels[] = [
                    'id' => $vessel->id,
                    'poll_id' => $poll->id,
                    'type' => $poll->type_id,
                    'imo' => $vessel->imo,
                    'official_number' => $vessel->official_number,
                    'name' => $vessel->name,
                    'ais_last_update' => $vessel->ais_last_update,
                    'zone_id' => $vessel->zone_id,
                    'zone_name' => $vessel->zone->name,
                ];
            }
        }

        return $vessels;
    }

    public static function getNetworkPolls($id, $type = false)
    {
        $polls = VesselAisPoll::where('repeating', 1)
            ->where([['status', '<>', 4]])
            ->whereHas('networks', function ($q) use ($id) {
                $q->where('networks.id', $id);
            });

        if ($type) {
            $polls = $polls->where('type', $type);
        }

        return $polls->get();
    }
    public static function getFleetPolls($id, $type = false)
    {
        $polls = VesselAisPoll::where('repeating', 1)
            ->where([['status', '<>', 4]])
            ->whereHas('fleets', function ($q) use ($id) {
                $q->where('fleets.id', $id);
            });

        if ($type) {
            $polls = $polls->where('type', $type);
        }

        return $polls->get();
    }

    public static function addNetworkPoll(
        $user,
        $type,
        $network_ids,
        $repeating = false,
        $repeating_interval = 5,
        $start_date = null,
        $stop_date = null
    ) {
        $poll = self::createPoll(
            $user,
            $type,
            $repeating,
            $repeating_interval,
            $start_date,
            $stop_date
        );

        $poll->networks()->sync($network_ids);

        $success = true;
        return [
            'success' => $success,
            'message' => 'Poll Updated',
            'poll_id' => $poll->id,
        ];
    }

    public static function addFleetPoll(
        $user,
        $type,
        $fleet_ids,
        $repeating = false,
        $repeating_interval = 5,
        $start_date = null,
        $stop_date = null
    ) {
        $poll = self::createPoll(
            $user,
            $type,
            $repeating,
            $repeating_interval,
            $start_date,
            $stop_date
        );

        $poll->fleets()->sync($fleet_ids);

        $success = true;
        return [
            'success' => $success,
            'message' => 'Poll Updated',
            'poll_id' => $poll->id,
        ];
    }

    public static function createPoll(
        $user,
        $type,
        $repeating = false,
        $repeating_interval = 5,
        $start_date = null,
        $stop_date = null
    ) {
        $poll_data = [
            'type_id' => $type,
            'company_id' => $user->company_id,
            'created_user_id' => $user->id,
            'status' => 1,
            'repeating' => $repeating ? 1 : 0,
            'repeating_interval_minutes' => $repeating_interval,
            'start_date' => $start_date,
            'stop_date' => $stop_date,
        ];

        $poll = VesselAisPoll::create($poll_data);

        return $poll;
    }

    public static function processPoll($imo, $poll_id)
    {
        $poll = VesselAisPoll::find($poll_id);

        if (empty($poll)) {
            echo "POLL?" . $poll_id;
            return false;
        }

        $type = $poll->type_id;
        // convenience flags
        $extended = false;
        $satelite = false;
        switch ($type) {
            case VesselAisApiCost::POS_TER_EXTENDED:
            case VesselAisApiCost::POS_SAT_EXTENDED:
            case VesselAisApiCost::TRACK_TER_EXTENDED:
            case VesselAisApiCost::TRACK_SAT_EXTENDED:
                $extended = true;
                break;
        }
        switch ($type) {
            case VesselAisApiCost::POS_SAT_SIMPLE:
            case VesselAisApiCost::POS_SAT_EXTENDED:
            case VesselAisApiCost::TRACK_SAT_SIMPLE:
            case VesselAisApiCost::TRACK_SAT_EXTENDED:
                $satelite = true;
                break;
        }

        $dateNow = new DateTime();
        $vessel = Vessel::where('imo', $imo)->first();

        $update = ['success' => false];
        switch ($type) {
            case VesselAisApiCost::POS_TER_SIMPLE:
            case VesselAisApiCost::POS_SAT_SIMPLE:
            case VesselAisApiCost::POS_TER_EXTENDED:
            case VesselAisApiCost::POS_SAT_EXTENDED:
                // don't run if just run
                if (!empty($vessel->ais_last_update)) {
                    $dateLastUpdate = new DateTime();
                    $last_update = $dateLastUpdate->setTimestamp(
                        strtotime($vessel->ais_last_update)
                    );
                    $interval = $last_update->diff($dateNow);

                    $intervalInSeconds = (new DateTime())
                        ->setTimeStamp(0)
                        ->add($interval)
                        ->getTimeStamp();
                    $intervalInMinutes = $intervalInSeconds / 60;
                    if ($intervalInMinutes < 2) {
                        echo $last_update->format('c');
                        echo "Last update: " .
                            $intervalInMinutes .
                            " minutes ago, skipping...\n";
                        break;
                    }
                }
                if ($extended) {
                    sleep(5); // extended responses limit frequency of requests
                } else {
                }
                $update = self::getVesselAIS($imo, $extended, $satelite, true);
                break;

            case VesselAisApiCost::TRACK_TER_SIMPLE:
            case VesselAisApiCost::TRACK_SAT_SIMPLE:
            case VesselAisApiCost::TRACK_TER_EXTENDED:
            case VesselAisApiCost::TRACK_SAT_EXTENDED:
                $update = self::getVesselTrack(
                    $imo,
                    -1,
                    -1,
                    $extended,
                    $satelite,
                    true
                );
                break;
            case VesselAisApiCost::PARTICULARS:
                $update = self::getVesselParticulars($vessel->imo, true);
                break;
            case VesselAisApiCost::PHOTOS:
                $update = self::getVesselPhoto($vessel, $imo, true);
                break;
        }
        echo print_r($update, true);
        // need to run live update queries because multiple threads...
        if ($update['success']) {
            $affected = DB::update(
                'update vessel_ais_poll set batch_success_count = batch_success_count + 1 where id = ?',
                [$poll->id]
            );
            //      $poll->batch_success_count = $poll->batch_success_count + 1;
        }
        $affected = DB::update(
            'update vessel_ais_poll set batch_processed_count = batch_processed_count + 1 where id = ?',
            [$poll->id]
        );
        if ($affected != 1) {
            echo "\n\n!!!!!!!\nERROR INCREMENTING PROCESSED COUNT!!!\n\n\n";
        }
        $poll->save();
    }

    public static function getVesselTrackData($id)
    {
        $vessel = Vessel::find($id);
        $track = VesselAISPositions::where('vessel_id', $id)->oldest('timestamp');
        $dateLastUpdate = new DateTime();
        $daysBefore = $dateLastUpdate
            ->setTimestamp(strtotime($vessel->ais_last_update))
            ->modify('-14 day')
            ->format('Y-m-d');
        $entries = VesselHistoricalTrack::where([
            ['vessel_id', '=', $id],
            ['ais_last_update', '>=', $daysBefore], //todo: option to go back so many days?
        ])
            ->take(50)
            ->get();

        if (count($entries) > 1) {
            return ['success' => true, 'track' => $entries];
        } else {
            return [
                'success' => false,
                'message' => 'No historical track data.',
            ];
        }
    }

    public static function getVesselTrack(
        $imo,
        $hourly = -1,
        $days = -1,
        $extended = false,
        $satellite = false,
        $console = false
    ) {
        $vessel = Vessel::where('imo', $imo)->first();

        $settings = VesselAisSettings::first();

        if ($hourly === -1) {
            $hourly = $settings->historical_track_period_all == "hourly";
        }

        if ($days === -1) {
            $days = $settings->historical_track_days_all;
        }

        $success = false;
        if (empty($vessel)) {
            return ['success' => $success, 'message' => 'Vessel not found'];
        }

        $daysBefore = (new DateTime())
            ->modify('-' . ($days + 1) . ' day')
            ->format('Y-m-d');
        $entries = VesselHistoricalTrack::where([
            ['vessel_id', '=', $vessel->id],
            ['ais_last_update', '>=', $daysBefore],
        ])->get();

        if (count($entries) > 10) {
            if ($console) {
                echo count($entries) . " entries found";
            }
            return ['success' => true, 'track' => $entries];
        }

        if ($satellite) {
            $apikey_ht = $value = config('services.mt.key_ht_sat');
        } else {
            $apikey_ht = $value = config('services.mt.key_ht');
        }

        $sandbox = config('services.mt.sandbox');
        if ($sandbox) {
            if ($extended) {
                $contents = self::DUMMY_TRACK_EXTENDED;
            } else {
                $contents = self::DUMMY_TRACK_SIMPLE;
            }
            $response = json_decode($contents);
        } else {
            $link =
                'https://services.marinetraffic.com/api/exportvesseltrack/v:2/' .
                $apikey_ht .
                '/imo:' .
                $imo .
                '/days:' .
                $days .
                '/period:' .
                ($hourly ? 'hourly' : 'daily') .
                '/msgtype:' .
                ($extended ? 'extended' : 'simple') .
                '/protocol:jsono';
            if ($console) {
                echo $link . "\n";
            }
            //try {
            $contents = file_get_contents($link);
            $response = json_decode($contents);
            if ($console) {
                echo "????CONTENTS\n";
            }
            if ($console) {
                print_r($contents);
            }
            if ($console) {
                echo "????\n";
            }
            if ($console) {
                echo "????OBJ\n";
            }
            if ($console) {
                print_r($response);
            }
            if ($console) {
                echo "????\n";
            }
        }
        if (empty($response) || count($response) == 0) {
            $success = false;
            return [
                'success' => $success,
                'message' => 'Empty response from API - HT',
            ];
        }
        if ($console) {
            echo "TRACK!!!\n";
        }
        if ($console) {
            print_r($response);
        }

        // get the most out of it
        for ($i = 0; $i < count($response) - 1; $i++) {
            // data here will also be used for current position
            // since last record is newest
            $data = $response[$i];
            $lat = $data->LAT;
            $lon = $data->LON;
            $update_time = $data->TIMESTAMP;
            $mt_status = $data->STATUS;
            $ais_status = AisStatus::where('status_id', $mt_status)->first();
            $status = $ais_status ? $ais_status->status_id : null;
            $speed = $data->SPEED;
            $course = $data->COURSE;
            $heading = $data->HEADING;
            $mmsi = $data->MMSI;

            $track_data = [
                'vessel_id' => $vessel->id,
                'ais_provider_id' => 1,
                'latitude' => $lat,
                'longitude' => $lon,
                'speed' => $speed,
                'course' => $course,
                'heading' => $heading,
                'ais_status_id' => $status,
                'ais_last_update' => $update_time,
                'ais_source' => isset($data->DSRC) ? $data->DSRC : 'HT',
            ];

            $track_update = VesselHistoricalTrack::create($track_data);
        }

        $entries = VesselHistoricalTrack::where([
            ['vessel_id', '=', $vessel->id],
            ['ais_last_update', '>=', $daysBefore],
        ])
            ->orderBy('ais_last_update', 'desc')
            ->get();

        return ['success' => true, 'track' => $entries];
    }

    public static function getVesselAIS(
        $imo,
        $extended = false,
        $satellite = false,
        $console = false
    ) {
        $vessel = Vessel::where('imo', $imo)->first();

        $success = false;
        if (empty($vessel)) {
            return ['success' => $success, 'message' => 'Vessel not found'];
        }
        if ($satellite) {
            $apikey_sp = $value = config('services.mt.key_sp_sat');
        } else {
            $apikey_sp = $value = config('services.mt.key_sp');
        }

        $sandbox = config('services.mt.sandbox');
        if ($sandbox) {
            if ($extended) {
                $contents = self::DUMMY_POS_EXTENDED;
            } else {
                $contents = self::DUMMY_POS_SIMPLE;
            }
            $response = json_decode($contents);
        } else {
            $link =
                'https://services.marinetraffic.com/api/exportvessel/v:5/' .
                $apikey_sp .
                '/imo:' .
                $imo .
                '/timespan:2880/msgtype:' .
                ($extended ? 'extended' : 'simple') .
                '/protocol:jsono';

            if ($console) {
                echo $link . "\n";
            }
            //try {
            $contents = file_get_contents($link);
            $response = json_decode($contents);
            if ($console) {
                echo "????CONTENTS\n";
            }
            if ($console) {
                print_r($contents);
            }
            if ($console) {
                echo "????\n";
            }
            if ($console) {
                echo "????OBJ\n";
            }
            if ($console) {
                print_r($response);
            }
            if ($console) {
                echo "????\n";
            }
        }
        if (empty($response) || !is_array($response) || count($response) == 0) {
            $success = false;
            return [
                'success' => $success,
                'message' => 'Empty response from API - SP',
            ];
        }

        $data = $response[0];

        if ($console) {
            echo "????\n";
        }
        if ($console) {
            print_r($data);
        }
        $lat = $data->LAT;
        $lon = $data->LON;
        $update_time = $data->TIMESTAMP;
        $mt_status = $data->STATUS;
        $ais_status = AisStatus::where('status_id', $mt_status)->first();
        $status = $ais_status ? $ais_status->status_id : null;
        $speed = intval($data->SPEED) / 10;
        $course = $data->COURSE;
        $heading = $data->HEADING;
        $mmsi = $data->MMSI;
        $track_data = [
            'vessel_id' => $vessel->id,
            'is_current_location' => 1,
            'ais_provider_id' => 1,
            'latitude' => $lat,
            'longitude' => $lon,
            'speed' => $speed,
            'course' => $course,
            'heading' => $heading, //just a fix
            'ais_status_id' => $status,
            'ais_last_update' => $update_time,
            'ais_source' => isset($data->DSRC) ? $data->DSRC : null,
        ];

        $vesselData = [
            'ais_provider_id' => 1,
            'ais_mmsi' => $data->MMSI,
            //'ais_imo' => $data->IMO, DOESN'T RETURN IMO FIELD
            'latitude' => $lat,
            'longitude' => $lon,
            'speed' => $speed,
            'course' => $course,
            'heading' => $heading, //just a fix
            'ais_status_id' => $status,
            'ais_last_update' => $update_time,
            'ais_source' => isset($data->DSRC) ? $data->DSRC : null,
        ];
        if ($extended) {
            /*
        CURRENT_PORT  text  The name of the Port the subject vessel is currently in (NULL if the vessel is underway)
LAST_PORT_TIME  date  The Date and Time (in UTC) that the subject vessel departed from the Last Port
DESTINATION text  The Destination of the subject vessel according to the AIS transmissions
PORT_ID integer A uniquely assigned ID by MarineTraffic for the Current Port
PORT_UNLOCODE text  A uniquely assigned ID by United Nations for the Current Port
LAST_PORT_ID  integer A uniquely assigned ID by MarineTraffic for the Last Port
LAST_PORT_UNLOCODE  text  A uniquely assigned ID by United Nations for the Last Port
ETA date  The Estimated Time of Arrival to Destination of the subject vessel according to the AIS transmissions
ETA_CALC  date  The Estimated Time of Arrival to Destination of the subject vessel according to the MarineTraffic calculations
NEXT_PORT_ID  integer A uniquely assigned ID by MarineTraffic for the Next Port
NEXT_PORT_UNLOCODE  text  A uniquely assigned ID by United Nations for the Next Port
NEXT_PORT_NAME  text  The Name of the Next Port as derived by MarineTraffic based on the subject vessel's reported Destination

            [SHIPNAME] => STI NOTTING HILL
            [SHIPTYPE] => 4
            [TYPE_NAME] => Oil/Chemical Tanker
            [AIS_TYPE_SUMMARY] => Tanker
            [CALLSIGN] => V7EU9
            [FLAG] => MH
            [LENGTH] => 183.11
            [WIDTH] => 32.2
            [DRAUGHT] => 113
            [GRT] => 29788 (Gross Tonnage)
            [DWT] => 49687 (Deadweight )
            [YEAR_BUILT] => 2015
            [DSRC] => TER
*/
            $last_port_time = new DateTime();
            $last_port_time = $last_port_time->setTimestamp(
                strtotime($data->LAST_PORT_TIME)
            );

            $eta = new DateTime();
            $eta = $eta->setTimestamp(strtotime($data->ETA));

            $eta_mt = new DateTime();
            $eta_mt = $eta_mt->setTimestamp(strtotime($data->ETA_CALC));

            $destination_port = null;
            // if (strlen($data->DESTINATION) > 0) {
            //   $destination_port = Port::where('unlocode', $data->DESTINATION)->first();
            // }

            $track_data['current_port'] =
                strlen($data->PORT_UNLOCODE) > 0
                    ? Port::where('unlocode', $data->PORT_UNLOCODE)->first()->id
                    : null;
            $track_data['last_port_time'] = $last_port_time;
            $track_data['destination'] = $data->DESTINATION;
            // same as next port anyway?
            //  if (!empty($destination_port)) {
            //    $track_data['destination_port_id'] = $destination_port->id;
            //  }
            $track_data['last_port'] =
                strlen($data->LAST_PORT_UNLOCODE) > 0
                    ? Port::where(
                        'unlocode',
                        $data->LAST_PORT_UNLOCODE
                    )->first()->id
                    : null;
            $track_data['eta'] = $eta;
            $track_data['eta_mt'] = $eta_mt;
            $track_data['next_port'] =
                strlen($data->NEXT_PORT_UNLOCODE) > 0
                    ? Port::where(
                        'unlocode',
                        $data->NEXT_PORT_UNLOCODE
                    )->first()->id
                    : null;

            $vesselData['ais_name'] = $data->SHIPNAME;
            $vesselData['ais_shiptype'] = $data->SHIPTYPE;
            $vesselData['ais_type_name'] = $data->TYPE_NAME;
            $vesselData['ais_type_summary'] = $data->AIS_TYPE_SUMMARY;
            $vesselData['ais_callsign'] = $data->CALLSIGN;
            $vesselData['ais_flag'] = $data->FLAG;
            $vesselData['ais_length'] = $data->LENGTH;
            $vesselData['ais_width'] = $data->WIDTH;
            $vesselData['ais_draught'] = $data->DRAUGHT;
            $vesselData['ais_gross_tonnage'] = $data->GRT;
            $vesselData['ais_deadweight'] = $data->DWT;
            $vesselData['ais_year_built'] = $data->YEAR_BUILT;
        }

        //$vesselData['zone_id'] = $track_data['zone_id'] = getGeoZoneID($vesselData['latitude'], $vesselData['longitude']);

        $exists_track = VesselHistoricalTrack::where($track_data);

        $vessel_update = $vessel->update($vesselData);

        $track_update = true;
        $vessel->track()->update([
            'is_current_location' => 0,
        ]);
        $track_update = VesselHistoricalTrack::create($track_data);

        if ($vessel_update && $track_update) {
            $success = true;
        } else {
            $success = false;
        }

        if ($console) {
            print '----------------';
            print 'ID: ' . $vessel->id;
            print 'Name: ' . $vessel->name;
            print 'IMO: ' . $imo;
            //                print('MMSI: ' . $mmsi);
            print 'Latitude: ' . $lat;
            print 'Longitude: ' . $lon;
            print 'Speed: ' . $speed;
            print 'Course: ' . $course;
            print 'Nav Status Code: ' . $mt_status . ' / ' . $status;
            print 'Last Update: ' . $update_time;
            print '----------------';
            if (!$success) {
                print 'ERROR Updating: ' . $imo;
            }
        }

        /*} catch (\Exception $error) {
      $count_error++;
      $this->error($error);
      $this->warn($imo . ' error updating');
//                $vessel = Vessel::where('mmsi', $mmsi);
//                $vessel->update([
//                    'showOnMap' => 0
//                ]);
  }*/
        return [
            'success' => $success,
            'message' => $success ? 'success' : 'error saving data',
            'position' => $track_data,
            'vessel_information' => $vesselData,
        ];
    }

    public static function getVesselPhoto($imo, $console = false)
    {
        $vessel = Vessel::where('imo', $imo)->first();

        $success = false;
        if (empty($vessel)) {
            return ['success' => $success, 'message' => 'Vessel not found'];
        }

        $apikey_photo = $value = config('services.mt.key_photo');

        $sandbox = config('services.mt.sandbox');
        if ($sandbox) {
            $contents = self::DUMMY_PHOTO;
            $response = json_decode($contents);
        } else {
            $link =
                'https://services.marinetraffic.com/api/exportvesselphoto/' .
                $apikey_photo .
                '/vessel_id:' .
                $vessel->imo .
                '/protocol:jsono';

            if ($console) {
                echo $link . "\n";
            }
            //try {
            $contents = file_get_contents($link);
            $response = json_decode($contents);
            if ($console) {
                echo "????CONTENTS\n";
            }
            if ($console) {
                print_r($contents);
            }
            if ($console) {
                echo "????\n";
            }
            if ($console) {
                echo "????OBJ\n";
            }
            if ($console) {
                print_r($response);
            }
            if ($console) {
                echo "????\n";
            }
        }

        if (count($response) == 0) {
            return ['success' => $success, 'message' => 'No Photo Data'];
        }
        $photo_url = $response[0]->URL;

        if ($console) {
            echo $photo_url . "\n";
        }

        $imageData = file_get_contents($photo_url);

        if ($console) {
            echo strlen($imageData) . "\n";
        }

        $imageSqr = Image::make($imageData);
        $imageSqr->fit(
            360,
            290,
            function ($constraint) {
                $constraint->upsize();
            },
            'bottom'
        );
        $imageRect = Image::make($imageData);
        $imageRect->fit(
            472,
            265,
            function ($constraint) {
                $constraint->upsize();
            },
            'bottom'
        );
        $directory = 'pictures/vessels/' . $vessel->id . '/';

        $nameRect = 'cover_rect.jpg';
        $nameSqr = 'cover_sqr.jpg';

        $vessel->ais_photo_url = $photo_url;
        $vessel->save();

        if (
            Storage::disk('gcs')->put(
                $directory . $nameSqr,
                (string) $imageSqr->encode('jpg'),
                'public'
            ) &&
            Storage::disk('gcs')->put(
                $directory . $nameRect,
                (string) $imageRect->encode('jpg'),
                'public'
            )
        ) {
            $success = true;

            $vessel->has_photo = true;
            $vessel->save();

            $directory = 'pictures/vessels/' . $vessel->id . '/';

            $file = 'cover_sqr.jpg';
            $url_sqr = Storage::disk('gcs')->url($directory . $file);

            $file = 'cover_rect.jpg';
            $url_rect = Storage::disk('gcs')->url($directory . $file);

            return [
                'success' => $success,
                'message' => 'success',
                'url_sqr' => $url_sqr,
                'url_rect' => $url_rect,
                'url_mt' => $photo_url,
            ];
        } else {
            return ['success' => $success, 'message' => 'error storing photos'];
        }
    }
    public static function getVesselParticulars($imo, $console = false)
    {
        $vessel = Vessel::where('imo', $imo)->first();

        $success = false;
        if (empty($vessel)) {
            return ['success' => $success, 'message' => 'Vessel not found'];
        }

        $apikey_part = $value = config('services.mt.key_part');
        $sandbox = config('services.mt.sandbox');
        if ($sandbox) {
            $contents = self::DUMMY_PARICULARS;
            $response = json_decode($contents);
        } else {
            $link =
                'https://services.marinetraffic.com/api/vesselmasterdata/v:3/' .
                $apikey_part .
                '/imo:' .
                $imo .
                '/protocol:jsono';

            if ($console) {
                echo $link . "\n";
            }
            //try {
            $contents = file_get_contents($link);
            $response = json_decode($contents);
            if ($console) {
                echo "????CONTENTS\n";
            }
            if ($console) {
                print_r($contents);
            }
            if ($console) {
                echo "????\n";
            }
            if ($console) {
                echo "????OBJ\n";
            }
            if ($console) {
                print_r($response);
            }
            if ($console) {
                echo "????\n";
            }
        }
        if (
            !isset($response->METADATA) ||
            $response->METADATA->TOTAL_RESULTS !== 1
        ) {
            return [
                'success' => $success,
                'message' => 'Vessel Not Found in API Data',
            ];
        }

        $data = $response->DATA[0];
        /*

stdClass Object
                (
                    [MMSI] => 544123120
                    [IMO] => 9085388
                    [NAME] => SUPER STAR
                    [PLACE_OF_BUILD] =>
                    [BUILD] => 1995
                    [BREADTH_EXTREME] => 32.2
                    [SUMMER_DWT] => 45999
                    [DISPLACEMENT_SUMMER] => 55811
                    [CALLSIGN] => C2AY2
                    [FLAG] => NR
                    [DRAUGHT] => 12.06
                    [LENGTH_OVERALL] => 183.2
                    [FUEL_CONSUMPTION] => 29 t/day at 14.00 kn
                    [SPEED_MAX] =>
                    [SPEED_SERVICE] => 15.3
                    [LIQUID_OIL] => 52719
                    [OWNER] => OCEAN GROW INTERNATIONAL SHIPMANAGEMENT
                    [MANAGER] => WINSON SHIPPING
                    [MANAGER_OWNER] =>
                    [VESSEL_TYPE] => OIL/CHEMICAL TANKER
                )

                */

        $vesselData = [
            'ais_name' => $data->NAME,
            'ais_place_of_build' => $data->PLACE_OF_BUILD,
            'ais_year_built' => $data->BUILD,
            'ais_breadth_extreme' => $data->BREADTH_EXTREME,
            'ais_deadweight' => $data->SUMMER_DWT,
            'ais_displacement' => $data->DISPLACEMENT_SUMMER,
            'ais_callsign' => $data->CALLSIGN,
            'ais_flag' => $data->FLAG,
            'ais_draught' => $data->DRAUGHT,
            'ais_length' => $data->LENGTH_OVERALL,
            'ais_fuel_consumption' => $data->FUEL_CONSUMPTION,
            'ais_speed_max' => $data->SPEED_MAX,
            'ais_speed_service' => $data->SPEED_SERVICE,
            'ais_liquid_oil' => $data->LIQUID_OIL,
            'ais_owner' => !empty($data->OWNER)
                ? $data->OWNER
                : $data->MANAGER_OWNER,
            'ais_manager' => !empty($data->MANAGER)
                ? $data->MANAGER
                : $data->MANAGER_OWNER,
            'ais_type_name' => $data->VESSEL_TYPE,
        ];

        if ($vessel->update($vesselData)) {
            $success = true;
            return [
                'success' => $success,
                'vesselData' => $vesselData,
                'message' => 'success',
            ];
        } else {
            return [
                'success' => $success,
                'vesselData' => $vesselData,
                'message' => 'Error saving information',
            ];
        }
    }
}
