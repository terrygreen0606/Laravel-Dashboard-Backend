<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserAddressResource;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Zone;
use Illuminate\Http\Request;

class UserAddressesController extends Controller
{
    public function index(User $user)
    {
        $userAddress = $user->address;
        if($userAddress) {
            if($userAddress->zone_id) {
                $zoneName = Zone::where('id', $userAddress->zone_id)->first()->name;
            } else {
                $zoneName = 'Outside US EEZ';
            }

            $results = [
                'id' => $userAddress->id,
                'user_id' => $userAddress->user_id,
                'co' => $userAddress->co,
                'street' => $userAddress->street,
                'unit' => $userAddress->unit,
                'city' => $userAddress->city,
                'province' => $userAddress->province,
                'state' => $userAddress->state,
                'country' => $userAddress->country,
                'zip' => $userAddress->zip,
                'phone' => $userAddress->phone,
                'latitude' => $userAddress->latitude,
                'longitude' => $userAddress->longitude,
                'zone_id' => $userAddress->zone_id,
                'zone_name' => $zoneName,
            ];
        } else {
            $results = [];
        }
        
        return response()->json($results);
    }

    public function updateAddress(User $user, Request $request)
    {
        $address = $user->address()->first();
        $addressData = \request('address');
        if ($addressData['street'] || $addressData['city']) {
            $geocoder = app('geocoder')->geocode($addressData['street'] . ' ' . $addressData['city'] . ' ' . $addressData['state'] . ' ' . $addressData['country'] . ' ' . $addressData['zip'])->get()->first();
            if ($geocoder) {
                $coordinates = $geocoder->getCoordinates();
                $latitude = $coordinates->getLatitude();
                $longitude = $coordinates->getLongitude();
            }
        }

        if($address) {
            $address['street'] = isset($addressData['street']) ? $addressData['street'] : $address['street'];
            $address['unit'] = isset($addressData['unit']) ? $addressData['unit'] : $address['unit'];
            $address['city'] = isset($addressData['city']) ? $addressData['city'] : $address['city'];
            $address['zip'] = isset($addressData['zip']) ? $addressData['zip'] : $address['zip'];
            $address['province'] = isset($addressData['province']) ? $addressData['province'] : $address['province'];
            $address['co'] = isset($addressData['co']) ? $addressData['co'] : $address['co'];
            $address['country'] = isset($addressData['country']) ? $addressData['country'] : $address['country'];
            $address['state'] = isset($addressData['state']) ? $addressData['state'] : $address['state'];
            $address['phone'] = isset($addressData['phone']) ? $addressData['phone'] : $address['phone'];
            $address['zone_id'] = isset($addressData['zone_id']) ? $addressData['zone_id'] : $address['zone_id'];
            $address['latitude'] = $latitude;
            $address['longitude'] = $longitude;
            if ($address->save()) {
                return response()->json(['message' => 'User address saved.']);
            }
            return response()->json(['message' => 'Something unexpected happened.']);
        } else {
            $addresses = UserAddress::create([
                'user_id'  => $user->id,
                'co'    => isset($addressData['co']) ? $addressData['co'] : '',
                'street' => isset($addressData['street']) ? $addressData['street'] : '',
                'unit' => isset($addressData['unit']) ? $addressData['unit'] : '',
                'city' => isset($addressData['city']) ? $addressData['city'] : '',
                'province' => isset($addressData['province']) ? $addressData['province'] : '',
                'state' => isset($addressData['state']) ? $addressData['state'] : '',
                'country' => isset($addressData['country']) ? $addressData['country'] : '',
                'zip' => isset($addressData['zip']) ? $addressData['zip'] : '',
                'phone' => isset($addressData['phone']) ? $addressData['phone'] : '',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'zone_id' => isset($addressData['zone_id']) ? $addressData['zone_id'] : '',
            ]);
            if ($addresses) {
                return response()->json(['message' => 'User address saved.']);
            }
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        
    }

    public function destroyAddress(UserAddress $address)
    {
        if ($address->delete()) {
            return response()->json(['message' => 'User address deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function store(User $user)
    {
        if ($user->address()->create(['street' => ''])) {
            return response()->json(['message' => 'User address added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }
}

