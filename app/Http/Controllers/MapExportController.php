<?php

namespace App\Http\Controllers;

use App\Http\Resources\VesselMapInfoResource;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Capability;
use App\Models\CapabilityField;
use App\Models\NavStatus;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vessel;
use App\Models\VesselAISPositions;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MapExportController extends Controller
{
    const iconDirectory = 'https://storage.googleapis.com/donjon-smit/map-icons/';

    public function KML($filters)
    {
        $mapFilters = json_decode($filters);

        // Creates an array of strings to hold the lines of the KML file.
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
        $kml[] = '<Document>';

        if ($mapFilters->vessels) $kml = $this->kmlVessel($kml, $filters);
        if ($mapFilters->individuals) $kml = $this->kmlIndividual($kml, $filters);
        if ($mapFilters->companies) $kml = $this->kmlCompany($kml, $filters);

        $kml[] = '</Document>';
        $kml[] = '</kml>';
        $kmlOutput = implode("\n", $kml);
        return response($kmlOutput)->header('Content-Type', 'application/vnd.google-earth.kml+xml');
    }

    private function kmlVessel($kml, $filters)
    {
        $vessels = MapController::getFilteredVessels($filters);

        $distinct_vessels = [];
        foreach ($vessels as $vessel) {
            $distinct_vessels[$vessel->ais_nav_status_id . $vessel->type->ais_category_id] = [
                'ais_nav_status_id' => $vessel->ais_nav_status_id,
                'vessel_type' => $vessel->type->ais_category_id,
            ];
        }

        foreach ($distinct_vessels as $distinct_vessel) {
            $id = $distinct_vessel['ais_nav_status_id'] . '-' . $distinct_vessel['vessel_type'];
            $kml[] = '<Style id="' . $id . '">';
            $kml[] = '<IconStyle>';
            $kml[] = '<scale>1</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getVesselIcon($distinct_vessel['vessel_type'], $distinct_vessel['ais_nav_status_id']) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }

        foreach ($vessels as $vessel) {
            $tracks = VesselAISPositions::where('vessel_id', $vessel['id'])
                ->orderBy('timestamp', 'desc')
                ->get();
            $latest = count($tracks) > 0 ? $tracks[0] : [];

            $kml[] = '<Placemark>';
            $kml[] = '<name>' . htmlentities($vessel['name']) . '</name>';
            $kml[] = '<description>' .
                htmlentities(
                    $vessel['name'] . ' ' .
                    '[' . ($vessel['imo'] ?? '--') . '] / ' .
                    ($latest['speed'] ?? '--') . 'knots / ' .
                    ($latest['course'] ?? '--') . '&deg;<br />' .
                    'AIS Status: ' . $vessel['nav_status']['value'] . '<br />' .
                    'Position received: ' . ($latest['timestamp'] ?? '--') . '<br />' .
                    'Destination: ' . ($latest['destination'] ?? '--') . '<br />' .
                    'ETA: ' . ($latest['eta'] ?? '--')
                ) .
                '</description>';
            $kml[] = '<styleUrl>#' . $vessel['ais_nav_status_id'] . '-' . $vessel['type']['ais_category_id'] . '</styleUrl>';
            $kml[] = '<Style>';
            $kml[] = '<IconStyle>';
            if ($vessel['ais_nav_status_id'] == 0)
                $kml[] =  '<heading>' . $vessel['ais_heading'] . '</heading>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $vessel['ais_long'] . ',' . $vessel['ais_lat'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        return $kml;
    }

    private function getVesselIcon($vesselType, $aisStatusId)
    {
        $validTypes = ['0', '1', '2', '3', '3a', '3b', '4', '5', '5a', '6', '7', '8', '9'];
        if (!$vesselType || !in_array($vesselType, $validTypes)) {
            $vesselType = 'Unspecified';
        }
        if (!$this->validateAISStatusID($aisStatusId)) {
            $aisStatusId = 'No_AIS';
        }
        return self::iconDirectory . 'vessels/' . $vesselType . '/' . $aisStatusId . '.png'; 
    }

    private function validateAISStatusID($aisStatusId)
    {
        $validStatusIDS = [0, 1, 2, 3, 4, 5, 6, 7, 8, 11, 12];
        return in_array($aisStatusId, $validStatusIDS);
    }

    private function kmlIndividual($kml, $filters)
    {
        $individuals = MapController::getFilteredIndividuals($filters, true);

        $distinctPrimaryServices = [];
        foreach($individuals as $individual) {
            $distinctPrimaryServices[$individual['primary_service']] = $individual['primary_service'];
        }

        foreach ($distinctPrimaryServices as $distinctPrimaryService) {
            $kml[] = '<Style id="individual-' . $distinctPrimaryService . '">';
            $kml[] = '<IconStyle>';
            $kml[] = '<scale>1</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getPrimaryServiceIcon('individuals', $distinctPrimaryService) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }

        foreach ($individuals as $individual) {
            $zone = Zone::find($individual['zone_id'])['name'] ?? 'Outside US EEZ';
            $kml[] = '<Placemark>';
            $kml[] = '<name>' .
                htmlentities($individual['first_name'] . ' ' . $individual['last_name']) .
                '</name>';
            $kml[] = '<description><![CDATA[' .
                'LatLng: ' . $individual['latitude'] . ' ' . $individual['longitude'] . '<br />' .
                'Zone: ' . $zone . '<br />' .
                'Phone: ' . ($individual['mobile_number'] ?? '--') . '<br />' .
                'Address: ' . $individual['street'] . ' ' . $individual['city'] . ', ' . $individual['state'] . ', ' . $individual['country'] . '<br />' .
                'E-mail: ' . $individual['email'] .
                ']]></description>';
            $kml[] = '<styleUrl>#individual-' . $individual['primary_service'] . '</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $individual['longitude'] . ',' . $individual['latitude'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        return $kml;
    }

    private function kmlCompany($kml, $filters)
    {
        $companies = MapController::getFilteredCompanies($filters);

        $distinctPrimaryServices = [];
        foreach ($companies as $company) {
            $distinctPrimaryServices[$company['primary_service']] = $company['primary_service'];
        }

        foreach ($distinctPrimaryServices as $distinctPrimaryService) {
            $kml[] = '<Style id="company-' . $distinctPrimaryService . '">';
            $kml[] = '<IconStyle>';
            $kml[] = '<scale>1</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getPrimaryServiceIcon('companies', $distinctPrimaryService) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }

        foreach ($companies as $company) {
            $zone = Zone::find($company['zone_id'])['name'] ?? 'Outside US EEZ';
            $kml[] = '<Placemark>';
            // '<![CDATA[' . $company['company']['name'] . ']]>'
            $kml[] = '<name><![CDATA[' . htmlentities($company['name']) . ']]></name>';
            $kml[] = '<description><![CDATA[' .
                'LatLng: ' . $company['latitude'] . ' ' . $company['longitude'] . '<br />' .
                'Zone: ' . $zone . '<br />' .
                'Phone: ' . ($company['phone'] ?? '--') . '<br />' .
                'Address: ' . $company['street'] . ', ' . $company['city'] . ', ' . $company['state'] . ', ' . $company['country'] . '<br />' .
                'E-mail: ' . $company['email'] .
                ']]></description>';
            $kml[] = '<styleUrl>#company-' . $company['primary_service'] . '</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $company['longitude'] . ',' . $company['latitude'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        return $kml;
    }

    private function getPrimaryServiceIcon($type, $primaryService)
    {
        $service = CapabilityField::select('code')
            ->where('id', $primaryService)
            ->whereIn('field_type', [-1, 0, 1])
            ->first()
            ['code'];
        if (!$service) {
            $service = 'undefined';
        }
        return self::iconDirectory . $type . '/' . $service . '.png';
    }

    public function KMLEarth()
    {
        // Creates an array of strings to hold the lines of the KML file.
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
        $kml[] = '<Folder>';
        $kml[] = '<NetworkLink>';
        $kml[] = '<Link>';
        $kml[] = '<href>' . url('/api/map/export/CDT.kml') . '</href>';
        $kml[] = '<refreshMode>onInterval</refreshMode>';
        $kml[] = '<refreshInterval>3600</refreshInterval>';
        $kml[] = '</Link>';
        $kml[] = '</NetworkLink>';
        $kml[] = '</Folder>';
        $kml[] = '</kml>';
        $kmlOutput = implode("\n", $kml);
        return response($kmlOutput)->header('Content-Type', 'application/vnd.google-earth.kml+xml');
    }
}
