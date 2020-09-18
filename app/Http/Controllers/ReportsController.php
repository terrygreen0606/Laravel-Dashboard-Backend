<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Vessel;
use App\Models\TrackChange;
use App\Models\ChangesTableName;
use App\Models\Action;
use App\Models\Frequency;
use App\Models\ReportSchedule;
use App\Models\ReportType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Laracsv\Export;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ReportsController extends Controller
{
    public function getNASAPotential()
    {
        $companies = Company::select('id', 'plan_number', 'name', 'email', 'fax', 'phone', 'website')->whereHas('networks', function ($q) {
            $q->where('networks.id', 4);
        })->withCount(['vessels' => function ($q1) {
            $q1->whereHas('networks', function ($q2) {
                $q2->where('networks.id', 4);
            });
        }])->orderBy('vessels_count', 'desc')->get();
        $csvExporter = new Export();
        $csvExporter->build($companies, ['vessels_count', 'name', 'id', 'plan_number', 'email', 'fax', 'phone', 'website'])->download();
    }

    public function getDJSVessels(Request $request)
    {
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';

        $djs_vessels = Vessel::where('active', 1)->with('vendors.hm', 'company.dpaContacts')->orderBy($sort, $sortDir)->get();
        //whereHas('company', function ($q) {
        //            $q->where('name', 'like', '%donjon%');
        //        })
        $report = [];
        foreach ($djs_vessels as $vessel) {
            $make['imo'] = $vessel->imo;
            $make['mmsi'] = $vessel->mmsi;
            $make['name'] = $vessel->name;
            $make['company'] = $vessel->company->first()->name;
            $make['country'] = $vessel->company->addresses()->first() ? $vessel->company->addresses()->first()->country : '';
            foreach ($vessel->vendors as $vendor) {
                if ($vendor->hm) {
                    $make['hm'] = $vendor->name;
                }
            }
            if ($vessel->company->dpaContacts->count()) {
                $make['dpa'] = $vessel->company->dpaContacts[0]->prefix . ' ' . $vessel->company->dpaContacts[0]->first_name . ' ' . $vessel->company->dpaContacts[0]->last_name;
                $make['dpa_email'] = $vessel->company->dpaContacts[0]->email;
                $make['dpa_work_phone'] = $vessel->company->dpaContacts[0]->work_phone;
                $make['dpa_mobile_phone'] = $vessel->company->dpaContacts[0]->mobile_phone;
                $make['dpa_aoh_phone'] = $vessel->company->dpaContacts[0]->aoh_phone;
                $make['dpa_fax'] = $vessel->company->dpaContacts[0]->fax;
            }
            $report[] = $make;
        }

        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($report);
        $currentPageItems = $itemCollection->slice(($currentPage * $per_page) - $per_page, $per_page)->all();

        $paginatedItems = new LengthAwarePaginator(array_values($currentPageItems), count($report), $per_page);
        return $paginatedItems;

        $csvExporter = new Export();
        $csvExporter->build(collect($report), ['imo' => 'IMO', 'mmsi' => 'MMSI', 'name' => 'Name', 'hm' => 'Hull and Machinery', 'dpa' => 'DPA', 'dpa_email' => 'DPA Email', 'dpa_work_phone' => 'DPA Work Phone', 'dpa_mobile_phone' => 'DPA Mobile Phone', 'dpa_aoh_phone' => 'DPA AOH Phone', 'dpa_fax' => 'DPA Fax'])->download();
    }

    public function exportActiveVessel()
    {
        $djs_vessels = Vessel::where('active', 1)->with('vendors.hm', 'company.dpaContacts')->orderBy('updated_at', 'desc')->get();
        $report = [];
        foreach ($djs_vessels as $vessel) {
            $make['imo'] = $vessel->imo;
            $make['mmsi'] = $vessel->mmsi;
            $make['name'] = $vessel->name;
            $make['company'] = $vessel->company->first()->name;
            $make['country'] = $vessel->company->addresses()->first() ? $vessel->company->addresses()->first()->country : '';
            foreach ($vessel->vendors as $vendor) {
                if ($vendor->hm) {
                    $make['hm'] = $vendor->name;
                }
            }
            if ($vessel->company->dpaContacts->count()) {
                $make['dpa'] = $vessel->company->dpaContacts[0]->prefix . ' ' . $vessel->company->dpaContacts[0]->first_name . ' ' . $vessel->company->dpaContacts[0]->last_name;
                $make['dpa_email'] = $vessel->company->dpaContacts[0]->email;
                $make['dpa_work_phone'] = $vessel->company->dpaContacts[0]->work_phone;
                $make['dpa_mobile_phone'] = $vessel->company->dpaContacts[0]->mobile_phone;
                $make['dpa_aoh_phone'] = $vessel->company->dpaContacts[0]->aoh_phone;
                $make['dpa_fax'] = $vessel->company->dpaContacts[0]->fax;
            }
            $report[] = $make;
        }

        return $report;
    }

    public function trackReport(Request $request)
    {
        $dates = $request->input('dates');

        if($dates) {
            if(count($dates) < 2) {
                return response()->json([ 'success' => false, 'message' => 'Please input correct dates!' ]); 
            }
        }

        if(!$dates) {
            $dates[0] = date("Y-m-d H:i:s", strtotime('-30days'));
            $dates[1] = date("Y-m-d H:i:s");
        }

        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';

        $changedTableId = (int)request('changed_table_name_id');
        if(request('action_id')) {
            $fieldIds = TrackChange::where([['changes_table_name_id', $changedTableId], ['action_id', request('action_id')]])->whereBetween('updated_at', array($dates[0], $dates[1]))->orderBy($sort, $sortDir)->get();
        } else {
            $fieldIds = TrackChange::where('changes_table_name_id', $changedTableId)->whereBetween('updated_at', array($dates[0], $dates[1]))->orderBy($sort, $sortDir)->get();
        }
    
        $results = [];
        foreach($fieldIds as $fieldId)
        {   
            switch ($changedTableId) {
                case 1: // Users Table
                    $changedRows = User::whereIn('id', explode(",", $fieldId->ids))->orderBy($sort, $sortDir)->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->username,
                            'company' => $changedRow->primary_company_id,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
                case 2: // Vessel Table
                    $changedRows = Vessel::whereIn('id', explode(",", $fieldId->ids))->orderBy($sort, $sortDir)->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->name,
                            'imo' => $changedRow->imo,
                            'official_number' => $changedRow->official_number,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
                case 3: // Company Table
                    $changedRows = Company::whereIn('id', explode(",", $fieldId->ids))->orderBy($sort, $sortDir)->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->username,
                            'plan_number' => $changedRow->plan_number,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
            }
        }
        

        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($results);
        $currentPageItems = $itemCollection->slice(($currentPage * $per_page) - $per_page, $per_page)->all();

        $paginatedItems = new LengthAwarePaginator(array_values($currentPageItems), count($results), $per_page);
        return $paginatedItems;
    }

    public function exportTrackReport()
    {
        $fieldIds = TrackChange::orderBy('updated_at', 'desc')->get();
    
        $results = [];
        foreach($fieldIds as $fieldId)
        {   
            switch ($fieldId->changes_table_name_id) {
                case 1: // Users Table
                    $changedRows = User::whereIn('id', explode(",", $fieldId->ids))->orderBy('updated_at', 'desc')->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->username,
                            'company' => $changedRow->primary_company_id,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
                case 2: // Vessel Table
                    $changedRows = Vessel::whereIn('id', explode(",", $fieldId->ids))->orderBy('updated_at', 'desc')->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->name,
                            'imo' => $changedRow->imo,
                            'official_number' => $changedRow->official_number,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
                case 3: // Company Table
                    $changedRows = Company::whereIn('id', explode(",", $fieldId->ids))->orderBy('updated_at', 'desc')->get();
                    foreach($changedRows as $changedRow)
                    {
                        $results[] = [
                            'name' => $changedRow->username,
                            'plan_number' => $changedRow->plan_number,
                            'date' => (string)$changedRow->updated_at,
                            'action' => Action::where('id', $fieldId->action_id)->first()->name,
                        ];
                    }
                break;
            }
        }
        
        return $results;
    }

    public function activeVesselReport(Request $request)
    {
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';

        $vesselTable = new Vessel;
        $vesselTableName = $vesselTable->table();
        $companyTable = new Company;
        $companyTableName = $companyTable->table();

        $resultsQuery = Vessel::from($vesselTableName . ' AS v')->select(
            DB::raw('v.id, v.imo, v.mmsi, v.name, vt.name as hm, c.email, c.phone, c.work_phone, c.aoh_phone, c.fax, c.name as company, ca.country'))
                ->distinct()
                ->where('v.active', 1)
                ->leftJoin($companyTableName . " AS c", 'v.company_id','=','c.id')
                ->leftJoin('company_addresses AS ca', 'c.id', '=', 'ca.company_id')
                ->leftJoin('vendor_types AS vt', 'vt.id', '=', 'c.vendor_type');
        $per_page = request('per_page') == -1  ? count($resultsQuery->get()) : request('per_page');

        $results = $resultsQuery->paginate($per_page);

        return $results;
    }

    public function noContractCompany(Request $request)
    {
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';

        $companyIds = Company::where('active', 1)->orderBy($sort, $sortDir)->pluck('id');

        $resultsQuery = Company::whereHas('dpaContacts', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        $per_page = request('per_page') == -1  ? count($resultsQuery->get()) : request('per_page');

        $results = $resultsQuery->paginate($per_page);

        return $results;
    }

    public function exportNoContractCompany()
    {
        $companyIds = Company::where('active', 1)->orderBy('updated_at', 'desc')->pluck('id');

        $resultsQuery = Company::whereHas('dpaContacts', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        // $per_page = request('per_page') == -1  ? count($resultsQuery->get()) : request('per_page');

        // $results = $resultsQuery->paginate($per_page);

        return $resultsQuery->get();
    }

    public function setReportSchedule(Request $request)
    {
        $reportTypeId = request('report_type_id');
        $frequencyId = request('frequency_id');
        $userIds = request('user_ids');
        if(!$userIds) {
            return response()->json(['success' => false, 'message' => 'User Id is not setting.']);
        }
        foreach($userIds as $userId)
        {
            ReportSchedule::create([
                'report_type_id' => $reportTypeId,
                'frequency_id' => $frequencyId,
                'user_id' => $userId
            ]);
        }

        return response()->json(['success' => true, 'message' => 'The schedule has been set.']);
    }

    public function changedTables()
    {
        return ChangesTableName::all();
    }

    public function actions()
    {
        return Action::all();
    }

    public function reportType()
    {
        return ReportType::all();
    }

    public function frequency()
    {
        return Frequency::all();
    }
}
