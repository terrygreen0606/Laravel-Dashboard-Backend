<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PierShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "FID"=> $this->FID,
            "ID_N"=> $this->ID_N,
            "NAV_UNIT_I"=> $this->NAV_UNIT_I,
            "UNLOCODE"=> $this->UNLOCODE,
            "NAV_UNIT_N"=> $this->NAV_UNIT_N,
            "LOCATION_D"=> $this->LOCATION_D,
            "FACILITY_T"=> $this->FACILITY_T,
            "STREET_ADD"=> $this->STREET_ADD,
            "CITY_OR_TO"=> $this->CITY_OR_TO,
            "STATE_POST"=> $this->STATE_POST,
            "ZIPCODE"=> $this->ZIPCODE,
            "COUNTY_NAM"=> $this->COUNTY_NAM,
            "COUNTY_FIP"=> $this->COUNTY_FIP,
            "CONGRESS"=> $this->CONGRESS,
            "CONGRESS_F"=> $this->CONGRESS_F,
            "WTWY_NAME"=> $this->WTWY_NAME,
            "PORT_NAME"=> $this->PORT_NAME,
            "MILE"=> $this->MILE,
            "BANK"=> $this->BANK,
            "LATITUDE1"=> $this->LATITUDE1,
            "LONGITUDE1"=> $this->LONGITUDE1,
            "OPERATORS"=> $this->OPERATORS,
            "OWNERS"=> $this->OWNERS,
            "PURPOSE"=> $this->PURPOSE,
            "HIGHWAY_NO"=> $this->HIGHWAY_NO,
            "RAILWAY_NO"=> $this->RAILWAY_NO,
            "LOCATION"=> $this->LOCATION,
            "DOCK"=> $this->DOCK,
            "COMMODITIE"=> $this->COMMODITIE,
            "CONSTRUCTI"=> $this->CONSTRUCTI,
            "MECHANICAL"=> $this->MECHANICAL,
            "REMARKS"=> $this->REMARKS,
            "VERTICAL_D"=> $this->VERTICAL_D,
            "DEPTH_MIN"=> $this->DEPTH_MIN,
            "DEPTH_MAX"=> $this->DEPTH_MAX,
            "BERTHING_L"=> $this->BERTHING_L,
            "BERTHING_T"=> $this->BERTHING_T,
            "DECK_HEIGH"=> $this->DECK_HEIGH,
            "DECK_HEIG1"=> $this->DECK_HEIG1,
            "SERVICE_IN"=> $this->SERVICE_IN,
            "SERVICE_TE"=> $this->SERVICE_TE,
        ];
    }
}
