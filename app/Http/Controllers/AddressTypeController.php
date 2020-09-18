<?php

namespace App\Http\Controllers;

use App\Models\AddressType;
use App\Http\Resources\AddressTypeResource;
use Illuminate\Http\Request;

class AddressTypeController extends Controller
{
    public function index()
    {
        return AddressTypeResource::collection(AddressType::all());
    }
}
