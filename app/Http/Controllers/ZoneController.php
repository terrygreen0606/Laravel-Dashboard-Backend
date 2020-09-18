<?php

namespace App\Http\Controllers;

use App\Http\Resources\ZoneShortResource;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function indexShort()
    {
        return ZoneShortResource::collection(Zone::all());
    }
}
