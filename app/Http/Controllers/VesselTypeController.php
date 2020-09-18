<?php

namespace App\Http\Controllers;

use App\Http\Resources\VesselTypeResource;
use App\Models\VesselType;
use Illuminate\Http\Request;

class VesselTypeController extends Controller
{
    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return VesselTypeResource::collection(VesselType::all());
    }
}
