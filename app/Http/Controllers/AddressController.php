<?php

namespace App\Http\Controllers;

use App\Models\AddressType;
use App\Http\Resources\AddressTypeResource;
use App\Models\Country;
use App\Http\Resources\CountryResource;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function types()
    {
        return AddressTypeResource::collection(AddressType::all());
    }

    public function countries()
    {
        return CountryResource::collection(Country::orderBy('name')->get());
    }
}
