<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Alert;
use DB;

class AlertController extends Controller
{
    //

    public function getAlerts()
    {
        $alerts[0] = Alert::select('id', 'contents', 'categories', 'active')->where('categories', 'dashboard')->get();
        $alerts[1] = Alert::select('id', 'contents', 'categories', 'active')->where('categories', 'menu bar')->get();
        $alerts[2] = Alert::select('id', 'contents', 'categories', 'active')->where('categories', 'ais_status')->get();

        $results = [
            'alerts'    =>  $alerts,
            'activedashboard' => Alert::where('categories', 'dashboard')->where('active', 1)->count(),
            'activemenubar' => Alert::where('categories', 'menu bar')->where('active', 1)->count() + Alert::where('categories', 'ais_status')->where('active', 1)->count(),
        ];
        return response()->json($results);
    }

    public function addNew(Request $request)
    {
        $alert = Alert::create([
            'contents' => $request->input('content'),
            'categories' => $request->input('categories'),
            'active'   => 1
        ]);

        if ($alert) {
            return response()->json(['message' => 'Alert added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function deleteAlert($id)
    {
        if (Alert::find($id)->delete()) {
            return response()->json(['message' => 'Alert deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function activeAlert($id, Request $request)
    {
        $active = $request->input('active');
        if (Alert::where('id', $id)->update(['active' => $active])) {
            return response()->json(['message' => 'Alert status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function updateAlert($id, Request $request)
    {
        $content = $request->input('content');
        if (Alert::where('id', $id)->update(['contents' => $content])) {
            return response()->json(['message' => 'Alert content updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function dashboardAlerts()
    {
        $result = Alert::select('id', 'contents', 'categories', 'active')->where('categories', 'dashboard')->where('active', 1)->get();
        return $result;
    }

    public function menubarAlerts()
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        $result = Alert::select('id', 'contents', 'categories', 'active')->where('categories', 'menu bar')->where('active', 1)->get();

        return $result;
    }
}
