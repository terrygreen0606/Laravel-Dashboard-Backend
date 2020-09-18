<?php

namespace App\Http\Controllers;

use App\Http\Resources\PierResource;
use App\Http\Resources\PierShowResource;
use Illuminate\Http\Request;
use App\Models\Pier;
class PierController extends Controller
{
    public function getAll(Request $request){
      /*  $per_page = !$request->has('per_page') ? 10 : (int)$request->get('per_page');
        if ($per_page < 0) {
            $per_page = 0;
        }*/
        $piers = Pier::all();

        return PierResource::collection($piers);
    }

    public function show($id){
        $pier =  Pier::find($id);
        return json_encode($pier);
    }
}
