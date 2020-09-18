<?php

namespace App\Http\Controllers;

use App\Http\Resources\VendorResource;
use App\Http\Resources\VendorShortResource;
use App\Http\Resources\VendorShowResource;
use App\Models\Company;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return VendorResource::collection(Company::where('vendor_active',1)->get());
    }

    public function getQI()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'QI Company');
        })->get());
    }

    public function getPI()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'P&I Club');
        })->get());
    }

    public function getResponse()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'Response');
        })->get());
    }

    public function getSocieties()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'Society');
        })->get());
    }

    public function getInsurers()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'H&M Insurer');
        })->get());
    }

    public function getProviders()
    {
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'Damage Stability Certificate Provider');
        })->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'vendor_type_id' => 'required'
        ]);

        $vendor = new Vendor();
        $vendor->name = request('name');
        $vendor->vendor_type_id = request('vendor_type_id');
        $vendor->shortname = request('shortname');
        $vendor->phone = request('phone');
        $vendor->fax = request('fax');
        $vendor->company_email = request('company_email');
        $vendor->address = request('address');

        if ($vendor->save()) {
            return response()->json(['message' => 'The vendor was added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function show($id)
    {
        return VendorShowResource::collection(Vendor::where('id', $id)->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'vendor_type_id' => 'required'
        ]);

        $vendor = Vendor::find($id);
        $vendor->name = request('name');
        $vendor->vendor_type_id = request('vendor_type_id');
        $vendor->shortname = request('shortname');
        $vendor->phone = request('phone');
        $vendor->fax = request('fax');
        $vendor->company_email = request('company_email');
        $vendor->address = request('address');

        if ($vendor->save()) {
            return response()->json(['message' => 'The vendor was saved.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vendor = Company::find($id);
        if ($vendor) {
            return $vendor->delete() ? response()->json(['message' => 'Vendor deleted.']) : response()->json(['message' => 'Could not delete vendor.']);
        }

        return response()->json(['message' => 'No vendor found.'], 404);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAll()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $vendors = $this->staticSearch(Company::latest(), \request('staticSearch'))->paginate($per_page);
        return VendorResource::collection($vendors);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getBySearch()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $uids = Vendor::search(request()->query('query'))->get('id');
        $ids = array();
        foreach ($uids as $u) {
            $ids[] = $u->id;
        }
        $vendors = $this->staticSearch(Vendor::whereIn('id', $ids), \request('staticSearch'))->paginate($per_page);
        return VendorResource::collection($vendors);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByOrder()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $vendors = $this->staticSearch(Vendor::orderBy($sortBy, $direction), \request('staticSearch'))->paginate($per_page);
        return VendorResource::collection($vendors);
    }

    private function staticSearch($model, $staticSearch)
    {
        if ($staticSearch['type'] !== -1) {
            $model = $model->where('vendor_type_id', $staticSearch['type']);
        }

        return $model;
    }
}
