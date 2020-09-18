<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\VesselFleets;
use App\Http\Resources\FleetResource;
use App\Http\Resources\FleetResourceShort;
use Illuminate\Http\Request;

use DB;

class FleetController extends Controller
{
    public function index()
    {
        return FleetResourceShort::collection(Fleet::all());
    }

    public function show($id)
    {
        return FleetResourceShort::collection(Fleet::where('id', $id)->get());
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $fleet = new Fleet();
        $fleet->name = request('name');

        if ($fleet->save()) {
            return response()->json(['message' => 'New fleet added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $fleet = Fleet::find($id);
        $fleet->name = request('name');

        if ($fleet->save()) {
            return response()->json(['message' => 'Fleet name updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroy($id)
    {
        $fleet = Fleet::find($id);

        if ($fleet) {
            if ($fleet->internal) {
                return response()->json(['message' => 'This fleet is internal.']);
            }

            VesselFleets::where('fleet_id',$id)->delete();

            return $fleet->delete() ? response()->json(['message' => 'Fleet deleted.']) : response()->json(['message' => 'Could not delete fleet.']);
        }

        return response()->json(['message' => 'No fleet found.'], 404);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAll(Request $request)
    {
        $per_page = !$request->has('per_page') ? 10 : (int)$request->get('per_page');
        if ($per_page < 0) {
            $per_page = 0;
        }
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';
        DB::enableQueryLog();
        $query = $request->get('query');
        if ($per_page < 0) {
            $per_page = 0;
        }
        $fleetModel = Fleet::orderBy($sort, $sortDir);
        if (!empty($query) && strlen($query) > 2) {
           $uids = Fleet::search($query)->get('id')->pluck('id');
           $fleetModel->whereIn('id', $uids);
        }
        $fleets = $this->staticSearch($fleetModel, \request('staticSearch'))->paginate($per_page);
        return FleetResource::collection($fleets);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getBySearch()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $uids = Fleet::search(request()->query('query'))->get('id');
        $ids = array();
        foreach ($uids as $u) {
            $ids[] = $u->id;
        }
        $fleets = $this->staticSearch(Fleet::whereIn('id', $ids), \request('staticSearch'))->paginate($per_page);
        return FleetResource::collection($fleets);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByOrder()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $fleets = $this->staticSearch(Fleet::orderBy($sortBy, $direction), \request('staticSearch'))->paginate($per_page);
        return FleetResource::collection($fleets);
    }

    private function staticSearch($model, $staticSearch)
    {
        if ($staticSearch['internal'] !== -1) {
            $model = $model->where('internal', 1);
        }

        return $model;
    }

    public function addVesselToFleets(Request $request)
    {
        $fleets = VesselFleets::where([['vessel_id', $request->input('vessel_id')], ['fleet_id', $request->input('fleet_id')]])->first();
        if(!$fleets) {
            VesselFleets::create([
                'vessel_id' => $request->input('vessel_id'),
                'fleet_id' => $request->input('fleet_id')
            ]);
            return response()->json(['message' => 'Vessel Added.']);
        } else {
            return response()->json(['message' => 'Vessel already exist.']);
        }
    }

    public function destroyVesselFromFleet ($id, $vessel)
    {
        VesselFleets::where([['fleet_id', $id], ['vessel_id', $vessel]])->delete();
        return response()->json(['message' => 'Vessel deleted.']);
    }
}
