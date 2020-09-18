<?php

namespace App\Helpers;

use App\Models\Vessel;
use App\Models\VesselAisApiCost;
use App\Models\VesselAisSettings;
use App\Models\VesselAISPositions;
use App\Models\VesselAISDetails;
use App\Models\VesselAisPoll;
use App\Models\VesselAISMTPoll;
use App\Helpers\GeoHelper;
use GuzzleHttp\Client;
use DateTime;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;

ini_set('max_execution_time', 10000);

class MT_API_Helper {

    const DUMMY_PHOTO = '[{"URL":"https:\/\/photos.marinetraffic.com\/ais\/showphoto.aspx?photoid=29709"}]';
    const DUMMY_PARICULARS = '{"METADATA":{"TOTAL_RESULTS":1,"TOTAL_PAGES":1,"CURRENT_PAGE":1},"DATA":[{"MMSI":"544123120","IMO":"9085388","NAME":"SUPER STAR","PLACE_OF_BUILD":"","BUILD":"1995","BREADTH_EXTREME":"32.2","SUMMER_DWT":"45999","DISPLACEMENT_SUMMER":"55811","CALLSIGN":"C2AY2","FLAG":"NR","DRAUGHT":"12.06","LENGTH_OVERALL":"183.2","FUEL_CONSUMPTION":"29 t\/day at 14.00 kn","SPEED_MAX":"","SPEED_SERVICE":"15.3","LIQUID_OIL":"52719","OWNER":"OCEAN GROW INTERNATIONAL SHIPMANAGEMENT","MANAGER":"WINSON SHIPPING","MANAGER_OWNER":"","VESSEL_TYPE":"OIL\/CHEMICAL TANKER"}]}';
    const DUMMY_POS_SIMPLE = '[{"MMSI":"241297000","LAT":"-29.761020","LON":"31.163010","SPEED":"1","HEADING":"172","COURSE":"243","STATUS":"1","TIMESTAMP":"2020-05-26T20:28:53","DSRC":"TER"}]';
    const DUMMY_POS_EXTENDED = '[{"MMSI":"244170000","LAT":"50.287190","LON":"-44.098710","SPEED":"116","HEADING":"33","COURSE":"29","STATUS":"0","TIMESTAMP":"2020-04-13T15:44:31","SHIPNAME":"JAN VAN GENT","SHIPTYPE":"70","TYPE_NAME":"General Cargo","AIS_TYPE_SUMMARY":"Cargo","IMO":"9456721","CALLSIGN":"PBUF","FLAG":"NL","PORT_ID":"","PORT_UNLOCODE":"","CURRENT_PORT":"","LAST_PORT_ID":"213","LAST_PORT_UNLOCODE":"USMSY","LAST_PORT":"NEW ORLEANS","LAST_PORT_TIME":"2020-03-31T13:19:00","DESTINATION":"ISGRT","ETA":"2020-04-17T07:00:00","ETA_CALC":"2020-04-17T06:46:00","LENGTH":"142.95","WIDTH":"18.9","DRAUGHT":"76","GRT":"8999","NEXT_PORT_ID":"19879","NEXT_PORT_UNLOCODE":"ISGRT","NEXT_PORT_NAME":"GRUNDARTANGI","NEXT_PORT_COUNTRY":"IS","DWT":"12007","YEAR_BUILT":"2009","DSRC":"SAT"}]';
    const DUMMY_TRACK_SIMPLE = '[{"MMSI":"636015050","STATUS":"0","SPEED":"111","LON":"-88.077590","LAT":"26.691060","COURSE":"13","HEADING":"3","TIMESTAMP":"2020-04-07T08:59:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"119","LON":"-87.722600","LAT":"29.767940","COURSE":"327","HEADING":"325","TIMESTAMP":"2020-04-08T00:29:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"28","LON":"-88.042200","LAT":"30.723160","COURSE":"174","HEADING":"353","TIMESTAMP":"2020-04-09T06:08:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"57","LON":"-88.031090","LAT":"30.662410","COURSE":"184","HEADING":"182","TIMESTAMP":"2020-04-10T00:37:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.099860","LAT":"30.080280","COURSE":"345","HEADING":"3","TIMESTAMP":"2020-04-11T00:01:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"0","LON":"-88.101860","LAT":"30.083420","COURSE":"353","HEADING":"95","TIMESTAMP":"2020-04-12T00:30:00","SHIP_ID":"755185"},{"MMSI":"636015050","STATUS":"0","SPEED":"3","LON":"-88.099040","LAT":"30.086620","COURSE":"220","HEADING":"181","TIMESTAMP":"2020-04-13T00:55:00","SHIP_ID":"755185"}]';

    public $vessels = [];
    public $extended;

    // Get Single Vessel Postions From MarineTraffic API
    public static function getAIS_PS07 ($id, $satellite, $extended)
    {
        // for single vessel and loop
        $vessel = Vessel::where('id', $id)->first();
        $imo = $vessel->imo;

        if (empty($imo)) {
            return ['success' => false, 'message' => 'Vessel not found'];
        }

        // select the api key
        if ($satellite) {
            $apiKey = config('services.mt.key_sp_sat');
        } else {
            $apiKey = config('services.mt.key_sp');
        }

        $apiUrl = 'https://services.marinetraffic.com/api/exportvessel/v:5/' . $apiKey . '/imo:' . $imo . '/timespan:2880/msgtype:' . (($extended) ? 'extended' : 'simple') . '/protocol:jsono';

        try {
            $results = file_get_contents($apiUrl);
        } catch (\Exception $error) {
            return ['success' => false, 'message' => 'Not enough credits for AIS call'];
        }

        // $results = self::DUMMY_POS_SIMPLE;
        $resData = json_decode($results);

        // validate the response data
        if (empty($resData) || !is_array($resData) || count($resData) == 0) {
            return ['success' => true, 'emptyData' => true, 'message' => 'Empty response from API'];
        }

        $aisData = $resData[0];
        $aisPosition = [];

        $zone_id = getGeoZoneID($aisData->LAT, $aisData->LON);

        // add or update the vessel_ais_positions table
        if($extended) {
            $aisPosition = VesselAISPositions::create([
                'vessel_id' => $id,
                'mmsi'      => isset($aisData->MMSI) ? $aisData->MMSI : '',
                'lat'       => isset($aisData->LAT) ? $aisData->LAT : '',
                'lon'       => isset($aisData->LON) ? $aisData->LON : '',
                'speed'     => isset($aisData->SPEED) ? ($aisData->SPEED / 10) : '',
                'heading'   => isset($aisData->HEADING) ? $aisData->HEADING : '',
                'course'    => isset($aisData->COURSE) ? $aisData->COURSE : '',
                'status'    => isset($aisData->STATUS) ? $aisData->STATUS : '',
                'timestamp' => isset($aisData->TIMESTAMP) ? $aisData->TIMESTAMP : '',
                'shipname'  => isset($aisData->SHIPNAME) ? $aisData->SHIPNAME : '',
                'shiptype'  => isset($aisData->SHIPTYPE) ? $aisData->SHIPTYPE : '',
                'type_name' => isset($aisData->TYPE_NAME) ? $aisData->TYPE_NAME : '',
                'ais_type_summary' => isset($aisData->AIS_TYPE_SUMMARY) ? $aisData->AIS_TYPE_SUMMARY : '',
                'imo'       => isset($aisData->IMO) ? $aisData->IMO : '',
                'callsign'  => isset($aisData->CALLSIGN) ? $aisData->CALLSIGN : '',
                'flag'      => isset($aisData->FLAG) ? $aisData->FLAG : '',
                'port_id'   => isset($aisData->PORT_ID) ? $aisData->PORT_ID : '',
                'port_unlocode' => isset($aisData->PORT_UNLOCODE) ? $aisData->PORT_UNLOCODE : '',
                'current_port'  => isset($aisData->CURRENT_PORT) ? $aisData->CURRENT_PORT : '',
                'last_port_id'  => isset($aisData->LAST_PORT_ID) ? $aisData->LAST_PORT_ID : '',
                'last_port_unlocode' => isset($aisData->LAST_PORT_UNLOCODE) ? $aisData->LAST_PORT_UNLOCODE : '',
                'last_port' => isset($aisData->LAST_PORT) ? $aisData->LAST_PORT : '',
                'last_port_time' => isset($aisData->LAST_PORT_TIME) ? $aisData->LAST_PORT_TIME : '',
                'destination' => isset($aisData->DESTINATION) ? $aisData->DESTINATION : $aisData->DESTINATION,
                'eta'       => isset($aisData->ETA) ? $aisData->ETA : '',
                'eta_calc'  => isset($aisData->ETA_CALC) ? $aisData->ETA_CALC : '',
                'length'    => isset($aisData->LENGTH) ? $aisData->LENGTH : '',
                'width'     => isset($aisData->WIDTH) ? $aisData->WIDTH : '',
                'draught'   => isset($aisData->DRAUGHT) ? $aisData->DRAUGHT : '',
                'grt'       => isset($aisData->GRT) ? $aisData->GRT : '',
                'dwt'       => isset($aisData->DWT) ? $aisData->DWT : '',
                'year_built'       => isset($aisData->YEAR_BUILT) ? $aisData->YEAR_BUILT : '',
                'next_port_id' => isset($aisData->NEXT_PORT_ID) ? $aisData->NEXT_PORT_ID : '',
                'next_port_unlocode' => isset($aisData->NEXT_PORT_UNLOCODE) ? $aisData->NEXT_PORT_UNLOCODE : '',
                'next_port_name' => isset($aisData->NEXT_PORT_NAME) ? $aisData->NEXT_PORT_NAME : '',
                'msgtype'   => 1,
                'dsrc'      => isset($aisData->DSRC) ? $aisData->DSRC : '',
                'zone_id'   => $zone_id,
            ]);
        } else {
            $aisPosition = VesselAISPositions::create([
                'vessel_id' => $id,
                'mmsi'      => isset($aisData->MMSI) ? $aisData->MMSI : '',
                'lat'       => isset($aisData->LAT) ? $aisData->LAT : '',
                'lon'       => isset($aisData->LON) ? $aisData->LON : '',
                'speed'     => isset($aisData->SPEED) ? ($aisData->SPEED / 10) : '',
                'heading'   => isset($aisData->HEADING) ? $aisData->HEADING : '',
                'course'    => isset($aisData->COURSE) ? $aisData->COURSE : '',
                'status'    => isset($aisData->STATUS) ? $aisData->STATUS : '',
                'timestamp' => isset($aisData->TIMESTAMP) ? $aisData->TIMESTAMP : '',
                'dsrc'      => isset($aisData->DSRC) ? $aisData->DSRC : '',
                'msgtype'   => 2,
                'zone_id'   => $zone_id,
            ]);
        }

        self::updateVesselAISInfo($id, $aisData);

        return ['success' => true, 'message' => 'success', 'position' => $aisPosition];
    }

    public function getAIS_PS07Bulk($vessels, $satellite, $extended)
    {
        $client = new Client();

        $this->extended = $extended;
        foreach($vessels as $vessel)
        {
            if(!empty($vessel->imo)) {
                $this->vessels[] = $vessel;
            }
        }

        $requests = function ($vessels, $satellite, $extended) {
            foreach ($vessels as $vessel) {
                // select the api key
                if ($satellite) {
                    $apiKey = config('services.mt.key_sp_sat');
                } else {
                    $apiKey = config('services.mt.key_sp');
                }

                $apiUrl = 'https://services.marinetraffic.com/api/exportvessel/v:5/' . $apiKey . '/imo:' . $vessel->imo . '/timespan:2880/msgtype:' . (($extended) ? 'extended' : 'simple') . '/protocol:jsono';
                yield new Request('GET', $apiUrl, [
                    'timeout' => 8,
                ]);
            }
        };

        $pool = new Pool($client, $requests($this->vessels, $satellite, $extended), [
            'concurrency' => 6,
            'fulfilled' => function (Response $response, $index) {
                // this is delivered each successful response
                $to = (string)$response->getBody();
                $aisData = json_decode((string)$response->getBody());
                if (!empty($aisData) && is_array($aisData) && count($aisData) !== 0) {
                    $aisData = $aisData[0];
                    $zone_id = getGeoZoneID($aisData->LAT, $aisData->LON);

                    // add or update the vessel_ais_positions table
                    if($this->extended) {
                        VesselAISPositions::create([
                            'vessel_id' => $this->vesselIds[$index]->id,
                            'mmsi'      => isset($aisData->MMSI) ? $aisData->MMSI : '',
                            'lat'       => isset($aisData->LAT) ? $aisData->LAT : '',
                            'lon'       => isset($aisData->LON) ? $aisData->LON : '',
                            'speed'     => isset($aisData->SPEED) ? ($aisData->SPEED / 10) : '',
                            'heading'   => isset($aisData->HEADING) ? $aisData->HEADING : '',
                            'course'    => isset($aisData->COURSE) ? $aisData->COURSE : '',
                            'status'    => isset($aisData->STATUS) ? $aisData->STATUS : '',
                            'timestamp' => isset($aisData->TIMESTAMP) ? $aisData->TIMESTAMP : '',
                            'shipname'  => isset($aisData->SHIPNAME) ? $aisData->SHIPNAME : '',
                            'shiptype'  => isset($aisData->SHIPTYPE) ? $aisData->SHIPTYPE : '',
                            'type_name' => isset($aisData->TYPE_NAME) ? $aisData->TYPE_NAME : '',
                            'ais_type_summary' => isset($aisData->AIS_TYPE_SUMMARY) ? $aisData->AIS_TYPE_SUMMARY : '',
                            'imo'       => isset($aisData->IMO) ? $aisData->IMO : '',
                            'callsign'  => isset($aisData->CALLSIGN) ? $aisData->CALLSIGN : '',
                            'flag'      => isset($aisData->FLAG) ? $aisData->FLAG : '',
                            'port_id'   => isset($aisData->PORT_ID) ? $aisData->PORT_ID : '',
                            'port_unlocode' => isset($aisData->PORT_UNLOCODE) ? $aisData->PORT_UNLOCODE : '',
                            'current_port'  => isset($aisData->CURRENT_PORT) ? $aisData->CURRENT_PORT : '',
                            'last_port_id'  => isset($aisData->LAST_PORT_ID) ? $aisData->LAST_PORT_ID : '',
                            'last_port_unlocode' => isset($aisData->LAST_PORT_UNLOCODE) ? $aisData->LAST_PORT_UNLOCODE : '',
                            'last_port' => isset($aisData->LAST_PORT) ? $aisData->LAST_PORT : '',
                            'last_port_time' => isset($aisData->LAST_PORT_TIME) ? $aisData->LAST_PORT_TIME : '',
                            'destination' => isset($aisData->DESTINATION) ? $aisData->DESTINATION : $aisData->DESTINATION,
                            'eta'       => isset($aisData->ETA) ? $aisData->ETA : '',
                            'eta_calc'  => isset($aisData->ETA_CALC) ? $aisData->ETA_CALC : '',
                            'length'    => isset($aisData->LENGTH) ? $aisData->LENGTH : '',
                            'width'     => isset($aisData->WIDTH) ? $aisData->WIDTH : '',
                            'draught'   => isset($aisData->DRAUGHT) ? $aisData->DRAUGHT : '',
                            'grt'       => isset($aisData->GRT) ? $aisData->GRT : '',
                            'dwt'       => isset($aisData->DWT) ? $aisData->DWT : '',
                            'year_built'       => isset($aisData->YEAR_BUILT) ? $aisData->YEAR_BUILT : '',
                            'next_port_id' => isset($aisData->NEXT_PORT_ID) ? $aisData->NEXT_PORT_ID : '',
                            'next_port_unlocode' => isset($aisData->NEXT_PORT_UNLOCODE) ? $aisData->NEXT_PORT_UNLOCODE : '',
                            'next_port_name' => isset($aisData->NEXT_PORT_NAME) ? $aisData->NEXT_PORT_NAME : '',
                            'msgtype'   => 1,
                            'dsrc'      => isset($aisData->DSRC) ? $aisData->DSRC : '',
                            'zone_id'   => $zone_id,
                            'to'        => $to
                        ]);
                    } else {
                        VesselAISPositions::create([
                            'vessel_id' => $this->vessels[$index]->id,
                            'mmsi'      => isset($aisData->MMSI) ? $aisData->MMSI : '',
                            'lat'       => isset($aisData->LAT) ? $aisData->LAT : '',
                            'lon'       => isset($aisData->LON) ? $aisData->LON : '',
                            'speed'     => isset($aisData->SPEED) ? ($aisData->SPEED / 10) : '',
                            'heading'   => isset($aisData->HEADING) ? $aisData->HEADING : '',
                            'course'    => isset($aisData->COURSE) ? $aisData->COURSE : '',
                            'status'    => isset($aisData->STATUS) ? $aisData->STATUS : '',
                            'timestamp' => isset($aisData->TIMESTAMP) ? $aisData->TIMESTAMP : '',
                            'dsrc'      => isset($aisData->DSRC) ? $aisData->DSRC : '',
                            'msgtype'   => 2,
                            'zone_id'   => $zone_id,
                            'to'        => $to
                        ]);
                    }

                    $vessel = Vessel::find($this->vessels[$index]->id);
                    if ($aisData->TIMESTAMP >= $vessel->ais_timestamp) {
                        $vessel->ais_timestamp = $aisData->TIMESTAMP;
                        $vessel->ais_lat = $aisData->LAT;
                        $vessel->ais_long = $aisData->LON;
                        $vessel->ais_heading = $aisData->HEADING;
                        $vessel->ais_nav_status_id = $aisData->STATUS;
                        $vessel->speed = $aisData->SPEED / 10;
                        $vessel->save();
                    }
                }
                // else {
                //     VesselAISPositions::create([
                //         'vessel_id' => $this->vesselIds[$index],
                //         'to' => '',
                //     ]);
                // }
            },
            'rejected' => function (RequestException $reason, $index) {
                // this is delivered each failed request
                VesselAISPositions::create([
                    'vessel_id' => $this->vessels[$index]->id,
                    'to' => 'timeout',
                ]);
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        return ['success' => true, 'message' => 'success'];
    }

    // Get HistoricalTrackData From MarineTraffic API
    public static function getAIS_PS01($id, $satellite, $extended)
    {
        $vessel = Vessel::where('id', $id)->first();
        $imo = $vessel->imo;

        $settings = VesselAisSettings::first();
        $days = $settings->historical_track_days_all;
        $hourly = $settings->historical_track_period_all;

        if (empty($vessel)) {
            return ['success' => false, 'message' => 'Vessel not found'];
        }

        if ($satellite) {
            $apiKey = config('services.mt.key_ht_sat');
            $dsrc = 'SAT';
        } else {
            $apiKey = config('services.mt.key_ht');
            $dsrc = 'TER';
        }

        $apiUrl = 'https://services.marinetraffic.com/api/exportvesseltrack/v:2/' . $apiKey . '/imo:' . $imo . '/days:' . $days . '/period:' . $hourly . '/msgtype:' . (($extended) ? 'extended' : 'simple') . '/protocol:jsono';

        try {
            $results = file_get_contents($apiUrl);
        } catch (\Exception $error) {
            return ['success' => false, 'message' => 'Not enough credits for AIS call'];
        }

        // $results = self::DUMMY_TRACK_SIMPLE;
        $resData = json_decode($results);

        // validate the response data
        if (empty($resData) || !is_array($resData) || count($resData) == 0) {
            return ['success' => false, 'message' => 'Empty response from API'];
        }

        for($i = 0; $i < count($resData); $i++) {

            $aisData = $resData[$i];
            $zone_id = getGeoZoneID($aisData->LAT, $aisData->LON);

            // add ais historical track data
            if($extended) {
                VesselAISPositions::create([
                    'vessel_id'     => $id,
                    'mmsi'          => $aisData->MMSI,
                    'status'        => $aisData->STATUS,
                    'speed'         => $aisData->SPEED / 10,
                    'lon'           => $aisData->LON,
                    'lat'           => $aisData->LAT,
                    'course'        => $aisData->COURSE,
                    'heading'       => $aisData->HEADING,
                    'timestamp'     => $aisData->TIMESTAMP,
                    'ship_id'       => $aisData->SHIP_ID,
                    'wind_angle'    => $aisData->WIND_ANGLE,
                    'wind_speed'    => $aisData->WIND_SPEED,
                    'wind_temp'     => $aisData->WIND_TEMP,
                    'zone_id'       => $zone_id,
                    'dsrc'          => $dsrc,
                ]);
            } else {
                VesselAISPositions::create([
                    'vessel_id' => $id,
                    'mmsi'      => $aisData->MMSI,
                    'status'    => $aisData->STATUS,
                    'speed'     => $aisData->SPEED / 10,
                    'lon'       => $aisData->LON,
                    'lat'       => $aisData->LAT,
                    'course'    => $aisData->COURSE,
                    'heading'   => $aisData->HEADING,
                    'timestamp' => $aisData->TIMESTAMP,
                    'ship_id'   => $aisData->SHIP_ID,
                    'zone_id'   => $zone_id,
                    'dsrc'      => $dsrc,
                ]);
            }
        }

        self::updateVesselAISInfo($id, last($resData));

        return ['success' => true, 'payload' => $resData];
    }

    // Get Vessel AIS Photo From MarineTraffic API
    public static function getAIS_VD01($imo)
    {
        $vessel = Vessel::where('imo', $imo)->first();

        if (empty($vessel)) {
            return ['success' => false, 'message' => 'Vessel not found'];
        }

        $apiKey = config('services.mt.key_photo');

        $apiUrl = 'https://services.marinetraffic.com/api/exportvesselphoto/' . $apiKey . '/vessel_id:' . $imo . '/protocol:jsono';

        try {
            $results = file_get_contents($apiUrl);
        } catch (\Exception $error) {
            return ['success' => false, 'message' => 'Not enough credits for AIS call'];
        }

        // $results = self::DUMMY_PHOTO;
        $resData = json_decode($results);

        // validate the response data
        if (count($resData) == 0) {
            return ['success' => false, 'message' => 'No Photo Data'];
        }

        $photo_url = $resData[0]->URL;
        if (!$photo_url) {
            return ['success' => false, 'message' => 'No Photo Data'];
        }

        $imageData = file_get_contents($photo_url);

        $imageSqr = Image::make( $imageData );
        $imageSqr->fit(360, 290, function ($constraint) {
                $constraint->upsize();
            }, 'bottom');
        $imageRect = Image::make( $imageData );
        $imageRect->fit(472, 265, function ($constraint) {
                $constraint->upsize();
            }, 'bottom');
        $imageFull = Image::make( $imageData );

        $directory = 'pictures/vessels/' . $vessel->id . '/';
        $photoDir = 'files/new/photos/' . $vessel->id . '/';
        $nameRect = 'cover_rect.jpg';
        $nameSqr = 'cover_sqr.jpg';
        $nameFull = 'full_image.jpg';

        $vessel->ais_photo_url = $photo_url;
        $vessel->save();

        if (Storage::disk('gcs')->put($directory.$nameSqr, (string)$imageSqr->encode('jpg'), 'public') &&
            Storage::disk('gcs')->put($directory.$nameRect, (string)$imageRect->encode('jpg'), 'public') &&
            Storage::disk('gcs')->put($photoDir.$nameFull, (string)$imageFull->encode('jpg'), 'public')) {

            $vessel->has_photo = true;
            $vessel->save();

            return [
                'success' => true,
                'message' => 'success',
            ];

        } else {
            return ['success' => false, 'message' => 'error storing photos'];
        }
    }

    // Get Particular AIS Data From MarineTraffic API
    public static function getAIS_VD02($id)
    {
        $vessel = Vessel::where('id', $id)->first();
        $imo = $vessel->imo;

        if (empty($vessel)) {
            return ['success' => false, 'message' => 'Vessel not found'];
        }

        $apiKey = config('services.mt.key_part');

        $apiUrl = 'https://services.marinetraffic.com/api/vesselmasterdata/v:3/' . $apiKey . '/imo:' . $imo .'/protocol:jsono';

        try {
            $results = file_get_contents($apiUrl);
        } catch (\Exception $error) {
            return ['success' => false, 'message' => 'Not enough credits for AIS call'];
        }

        // $results = self::DUMMY_PARICULARS;
        $resData = json_decode($results);
        // validate the response data
        // TO CHECK AGAIN AFTER CHECKING API REPONSE
        // Uncomment this
        if (empty((array)$resData) || !is_array((array)$resData) || count((array)$resData) == 0) {
            return ['success' => false, 'message' => 'Empty response from API'];
        }

        $aisData = $resData->DATA[0];
        $toCreate = [
            'vessel_id' => $id,
            'mmsi'      => $aisData->MMSI,
            'imo'       => $aisData->IMO,
            'name'      => $aisData->NAME,
            'place_of_build' => $aisData->PLACE_OF_BUILD,
            'build'     => $aisData->BUILD,
            'breadth_extreme'   => $aisData->BREADTH_EXTREME,
            'summer_dwt' => $aisData->SUMMER_DWT,
            'displacement_summer' => $aisData->DISPLACEMENT_SUMMER,
            'callsign'  => $aisData->CALLSIGN,
            'flag'      => $aisData->FLAG,
            'draught'   => $aisData->DRAUGHT,
            'length_overall' => $aisData->LENGTH_OVERALL,
            'fuel_consumption' => $aisData->FUEL_CONSUMPTION,
            'speed_max' => $aisData->SPEED_MAX,
            'speed_service' => $aisData->SPEED_SERVICE,
            'liquid_oil' => $aisData->LIQUID_OIL,
            'owner'     => $aisData->OWNER,
            'manager'   => $aisData->MANAGER,
            'vessel_type' => $aisData->VESSEL_TYPE,
            'manager_owner' => $aisData->MANAGER_OWNER,
        ];

        // add ais detail to vessel_ais_details table
        if (VesselAISDetails::create($toCreate)) {
            return ['success' => true, 'message' => 'success', 'particular' => $toCreate];
        }

        return ['success' => false, 'message' => 'Something unexpected happened.'];
    }

    // Calculate The Vessel Cost From DB
    public static function costVesselsAISPoll($type, $vessel_ids, $network_ids, $fleet_ids, $repeating, $repeating_interval)
    {
        $settings = VesselAisSettings::first();

        // convenience flags
        $satellite = false;
        switch ($type) {
            case VesselAisApiCost::POS_SAT_SIMPLE:
            case VesselAisApiCost::POS_SAT_EXTENDED:
            case VesselAisApiCost::TRACK_SAT_SIMPLE:
            case VesselAisApiCost::TRACK_SAT_EXTENDED:
                $satellite = true;
                break;
        }

        if ($repeating && intval($repeating_interval) < 2) {
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
            $network_vessels = Vessel::select(['vessels.*'])
                ->distinct()
                ->join("companies AS c1", 'vessels.company_id','=','c1.id')
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->whereIn('nc.network_id', $network_ids)
                ->get();
            $vessel_count = count($network_vessels);
        } else if (!empty($fleet_ids)) {
            $fleet_vessels = Vessel::select(['vessels.*'])
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

        // NOTE: if track data, cost is multiplied by some formula
        if ($type->id >= 7) {
            $cost *=
                $settings->historical_track_days_all *
                ($settings->historical_track_period_all == 'daily' ? 1 : 24);
        }

        return response()->json(['success' => true, 'cost' => $cost, 'per_hour' => $repeating, 'vessel_count' => $vessel_count]);
    }

    public static function getAllVesselsWithPolls()
    {
        $polls = VesselAISMTPoll::all();
        $contVessels = [];
        foreach($polls as $poll) {
            $vessel = Vessel::where('id', $poll->vessel_id)->first();
            $contVessels[] = [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'zone_id' => $vessel->zone_id,
                'zone_name' => $vessel->zone->name,
                'ais_timestamp' => $vessel->ais_timestamp
            ];
        }

        return $contVessels;
    }

    /**
     * Update Vessel's AIS data with the new aisData
     *
     * @param [id] $id
     * @param [object] $aisData
     * @return void
     */
    public static function updateVesselAISInfo($id, $aisData)
    {
        $vessel = Vessel::find($id);
        if ($aisData->TIMESTAMP >= $vessel->ais_timestamp) {
            $vessel->ais_timestamp = $aisData->TIMESTAMP;
            $vessel->ais_lat = $aisData->LAT;
            $vessel->ais_long = $aisData->LON;
            $vessel->ais_heading = $aisData->HEADING;
            $vessel->ais_nav_status_id = $aisData->STATUS;
            $vessel->speed = $aisData->SPEED / 10;
            $vessel->save();
        }
    }
}
