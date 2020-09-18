<?php

namespace App\Http\Controllers;

use App\Http\Resources\NetworkResourceShort;
use App\Models\Network;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function indexShort()
    {
        return NetworkResourceShort::collection(Network::all());
    }
}
