<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Vessel;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class CompanyDocController extends Controller
{
    public function generate(Company $company, $type, $location, Request $request)
    {
      if(file_exists(storage_path('documents/templates/' . $type . '.docx'))){
        $template = new TemplateProcessor(storage_path('documents/templates/' . $type . '.docx'));
        $fileName = str_slug($company->name) . '--' . $type . '--' . date('m-d-Y_h_ia');
        $extension = '.docx';
        $issueDate = date('F d, Y');
        switch ($type) {
            case 'written-consent-agreement-group-v':
                $fileName = str_slug($company->name) . ' - Written Consent Agreement - Group V - ' . date('m-d-Y_h_ia');
                $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName', sanitize_data_for_doc(request('name')));
                $template->setValue('companyAddress', $address);
                $template->setValue('issueDate', $issueDate);
                break;
            case 'written-consent-agreement-non-tank-vessels-below-250-bbls':
                $fileName = str_slug($company->name) . ' - Written Consent Agreement - Below 250 bbls - ' . date('m-d-Y_h_ia');
                $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName', sanitize_data_for_doc(request('name')));
                $template->setValue('companyAddress', $address);
                $template->setValue('issueDate', $issueDate);
                break;
            case 'written-consent-agreement-non-tank-vessels-below-2500-bbls':
                $fileName = str_slug($company->name) . ' - Written Consent Agreement - Below 2500 bbls - ' . date('m-d-Y_h_ia');
                $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName', sanitize_data_for_doc(request('name')));
                $template->setValue('companyAddress', $address);
                $template->setValue('issueDate', $issueDate);
                /*
                 * $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName',request('name'));
                $template->setValue('companyAddress',  $address);
                $template->setValue('issueDate', $issueDate);
                 * */
                break;
            case 'smff-coverage-certification':
                $fileName = str_slug($company->name) . '- SMFF Coverage Certification - ' . date('m-d-Y_h:ia');
                $template->setValue('companyName',sanitize_data_for_doc( $company->name));
                $template->setValue('issueDate', $issueDate);
                $vessels = $company->vessels()->select('id', 'name')->where('active',1)->orderBy('name')->get();
                $columns = 4;
                $rows = ceil(count($vessels) / $columns);
                $template->cloneRow('c1VesselName', $rows);
                for ($row = 1; $row <= $rows; $row++) {
                    for ($col = 1; $col <= $columns; $col++) {
                        $index = $col - 1 + $columns * ($row - 1);
                        if ($index < count($vessels)) {
                            $name = ucwords(strtolower($vessels[$index]->name));
                            $template->setValue('c' . $col . 'VesselName#' . $row,
                                htmlspecialchars(sanitize_data_for_doc($name), ENT_COMPAT, 'UTF-8'));
                        } else {
                            $template->setValue('c' . $col . 'VesselName#' . $row, '');
                        }
                    }
                }
                break;
            case 'damage-stability-coverage-certification':
                break;
            case 'nt-smff-annex':
                break;
            case 'tank-smff-annex':
                break;
            case 'combined-smff-annex':
                break;
            case 'schedule-a-non-tank':
                $fileName = str_slug($company->name) . ' - Schedule A Non-Tank - ' . date('m-d-Y_h_ia');
                $dpa = $company->contacts()->whereHas('contactTypes', function ($q) {
                    $q->where('name', 'DPA');
                })->first();
                $template->setValue('companyName', sanitize_data_for_doc($company->name));
                if ($dpa) {
                    $template->setValue('dpaName', $dpa->prefix . ' ' . sanitize_data_for_doc($dpa->first_name) . ' ' . sanitize_data_for_doc($dpa->last_name));
                    $template->setValue('dpaPhone', $dpa->work_phone);
                    $template->setValue('dpaMobile', $dpa->mobile_phone);
                    $template->setValue('dpaAohPhone', $dpa->aoh_phone);
                    $template->setValue('dpaFax', $dpa->fax);
                    $template->setValue('dpaEmail', $dpa->email);
                } else {
                    $template->setValue('dpaName', ' // ');
                    $template->setValue('dpaPhone', ' // ');
                    $template->setValue('dpaMobile', ' // ');
                    $template->setValue('dpaAohPhone', ' // ');
                    $template->setValue('dpaFax', ' // ');
                    $template->setValue('dpaEmail', ' // ');
                }
                if($company->qi_id > 0){
                    $qi = Company::where('id',$company->qi_id)->first();
                    $template->setValue('qiName', sanitize_data_for_doc($qi->name));
                } else {
                    $template->setValue('qiName', ' // ');
                }
                $vessels = $company->vessels()->where('active',1)->where('tanker', 0)->with('type:id,name')->orderBy('name')->get();
                $template->cloneRow('cVesselName', count($vessels));
                for ($row = 1, $rowMax = count($vessels); $row <= $rowMax; $row++) {
                    $name = ucwords(strtolower($vessels[$row - 1]->name));
                    $template->setValue('cVesselName#' . $row, sanitize_data_for_doc($name));
                    $template->setValue('cImo#' . $row, $vessels[$row - 1]->imo);
                    $template->setValue('cVesselType#' . $row, $vessels[$row - 1]->type->name);
                    $template->setValue('cDWT#' . $row, $vessels[$row - 1]->dead_weight);
                    $template->setValue('cDWT#' . $row, $vessels[$row - 1]->dead_weight);
                    $template->setValue('cDeckArea#' . $row, $vessels[$row - 1]->deck_area);
                    $template->setValue('cLCT#' . $row, $vessels[$row - 1]->oil_tank_volume);
                    $template->setValue('cOilGroup#' . $row, $vessels[$row - 1]->oil_group);
                    $society = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Society');
                    })->first();
                    if ($society) {
                        $template->setValue('cClass#' . $row, htmlspecialchars($society->shortname, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cClass#' . $row, ' // ');
                    }
                    $pi = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'P&I Club');
                    })->first();
                    if ($pi) {
                        $template->setValue('cPI#' . $row, htmlspecialchars($pi->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cPI#' . $row, ' // ');
                    }
                    $hm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'H&M Insurer');
                    })->first();
                    if ($hm) {
                        $template->setValue('cHM#' . $row, htmlspecialchars(sanitize_data_for_doc($hm->name), ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cHM#' . $row, ' // ');
                    }
                    $dsm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Damage Stability Certificate Provider');
                    })->first();
                    if ($dsm) {
                        $template->setValue('cDamageStability#' . $row, htmlspecialchars(sanitize_data_for_doc($dsm->name), ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cDamageStability#' . $row, ' // ');
                    }
                }
                break;
            case 'schedule-a-tanker':
                $fileName = str_slug($company->name) . ' - Schedule A Tanker - ' . date('m-d-Y_h_ia');
                $dpa = $company->contacts()->whereHas('contactTypes', function ($q) {
                    $q->where('name', 'DPA');
                })->first();
                $template->setValue('companyName', sanitize_data_for_doc($company->name));
                if ($dpa) {
                    $template->setValue('dpaName', $dpa->prefix . ' ' .sanitize_data_for_doc($dpa->first_name) . ' ' . sanitize_data_for_doc($dpa->last_name));
                    $template->setValue('dpaPhone', $dpa->work_phone);
                    $template->setValue('dpaMobile', $dpa->mobile_phone);
                    $template->setValue('dpaAohPhone', $dpa->aoh_phone);
                    $template->setValue('dpaFax', $dpa->fax);
                    $template->setValue('dpaEmail', $dpa->email);
                } else {
                    $template->setValue('dpaName', ' // ');
                    $template->setValue('dpaPhone', ' // ');
                    $template->setValue('dpaMobile', ' // ');
                    $template->setValue('dpaAohPhone', ' // ');
                    $template->setValue('dpaFax', ' // ');
                    $template->setValue('dpaEmail', ' // ');
                }
                if($company->qi_id > 0){
                    //return $company->qi_id;
                    $qi = Company::where('id',$company->qi_id)->first();
                    $template->setValue('qiName', sanitize_data_for_doc($qi->name));

                } else {
                    $template->setValue('qiName', ' // ');
                }
                $vessels = $company->vessels()->where('active',1)->where('tanker', 1)->orderBy('name')->get();


                $template->cloneRow('cVesselName', count($vessels));
                for ($row = 1; $row <= count($vessels); $row++) {
                    $name = ucwords(strtolower(sanitize_data_for_doc($vessels[$row - 1]->name)));
                    $template->setValue('cVesselName#' . $row, $name);
                    $template->setValue('cImo#' . $row, $vessels[$row - 1]->imo ?? '//');
                    $template->setValue('cVesselType#' . $row, $vessels[$row - 1]->type->name);
                    $template->setValue('cDWT#' . $row, $vessels[$row - 1]->dead_weight);
                    $template->setValue('cDeckArea#' . $row, $vessels[$row - 1]->deck_area);
                    $template->setValue('cLCT#' . $row, $vessels[$row - 1]->oil_tank_volume);
                    $template->setValue('cOilGroup#' . $row, $vessels[$row - 1]->oil_group);
                    $society = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Society');
                    })->first();
                    if ($society) {
                        $template->setValue('cClass#' . $row, htmlspecialchars($society->shortname, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cClass#' . $row, ' // ');
                    }
                    $pi = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'P&I Club');
                    })->first();
                    if ($pi) {
                        $template->setValue('cPI#' . $row, htmlspecialchars($pi->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cPI#' . $row, ' // ');
                    }
                    $hm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'H&M Insurer');
                    })->first();
                    if ($hm) {
                        $template->setValue('cHM#' . $row, htmlspecialchars($hm->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cHM#' . $row, ' // ');
                    }
                    $dsm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Damage Stability Certificate Provider');
                    })->first();
                    if ($dsm) {
                        $template->setValue('cDamageStability#' . $row, htmlspecialchars($dsm->name, ENT_COMPAT, 'UTF-8'));
                        $template->setValue('cDamageStability#' . $row, htmlspecialchars($dsm->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cDamageStability#' . $row, ' // ');
                    }
                }
                break;
            case 'schedule-a-combined':
                $fileName = str_slug($company->name) . ' - Schedule A Combined - ' . date('m-d-Y_h_ia');
                $dpa = $company->contacts()->whereHas('contactTypes', function ($q) {
                    $q->where('name', 'DPA');
                })->first();
                $template->setValue('companyName', $company->name);
                if ($dpa) {
                    $template->setValue('dpaName', $dpa->prefix . ' ' . sanitize_data_for_doc($dpa->first_name) . ' ' . sanitize_data_for_doc($dpa->last_name));
                    $template->setValue('dpaPhone', $dpa->work_phone);
                    $template->setValue('dpaMobile', $dpa->mobile_phone);
                    $template->setValue('dpaAohPhone', $dpa->aoh_phone);
                    $template->setValue('dpaFax', $dpa->fax);
                    $template->setValue('dpaEmail', $dpa->email);
                } else {
                    $template->setValue('dpaName', ' // ');
                    $template->setValue('dpaPhone', ' // ');
                    $template->setValue('dpaMobile', ' // ');
                    $template->setValue('dpaAohPhone', ' // ');
                    $template->setValue('dpaFax', ' // ');
                    $template->setValue('dpaEmail', ' // ');
                }
                if($company->qi_id > 0){
                    $qi = Company::where('id',$company->qi_id)->first();
                    $template->setValue('qiName', sanitize_data_for_doc($qi->name));
                }else {
                    $template->setValue('qiName', ' // ');
                }
                $vessels = $company->vessels()->where('active',1)->with('type:id,name')->orderBy('name')->get();
                $template->cloneRow('cVesselName', count($vessels));
                for ($row = 1, $rowMax = count($vessels); $row <= $rowMax; $row++) {
                    $name = ucwords(strtolower($vessels[$row - 1]->name));
                    $template->setValue('cVesselName#' . $row, sanitize_data_for_doc($name));
                    $template->setValue('cImo#' . $row, $vessels[$row - 1]->imo);
                    $template->setValue('cVesselType#' . $row, sanitize_data_for_doc($vessels[$row - 1]->type->name));
                    $template->setValue('cDWT#' . $row, sanitize_data_for_doc($vessels[$row - 1]->dead_weight));
                    $template->setValue('cDeckArea#' . $row, sanitize_data_for_doc($vessels[$row - 1]->deck_area));
                    $template->setValue('cLCT#' . $row, sanitize_data_for_doc($vessels[$row - 1]->oil_tank_volume));
                    $template->setValue('cOilGroup#' . $row,sanitize_data_for_doc( $vessels[$row - 1]->oil_group));
                    $society = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Society');
                    })->first();
                    if ($society) {
                        $template->setValue('cClass#' . $row, htmlspecialchars($society->shortname, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cClass#' . $row, ' // ');
                    }
                    $pi = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'P&I Club');
                    })->first();
                    if ($pi) {
                        $template->setValue('cPI#' . $row, htmlspecialchars($pi->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cPI#' . $row, ' // ');
                    }
                    $hm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'H&M Insurer');
                    })->first();
                    if ($hm) {
                        $template->setValue('cHM#' . $row, htmlspecialchars($hm->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cHM#' . $row, ' // ');
                    }
                    $dsm = $vessels[$row - 1]->vendors()->whereHas('type', function ($q) {
                        $q->where('name', 'Damage Stability Certificate Provider');
                    })->first();
                    if ($dsm) {
                        $template->setValue('cDamageStability#' . $row, htmlspecialchars($dsm->name, ENT_COMPAT, 'UTF-8'));
                    } else {
                        $template->setValue('cDamageStability#' . $row, ' // ');
                    }
                }
                break;
            case 'multiple-vessels-pre-fire-plan-certification':
                $fileName = str_slug($company->name) . ' - VPFP Certification - ' . date('m-d-Y_h_ia');
                $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName',sanitize_data_for_doc(request('name')));
                $template->setValue('companyAddress',  $address);
                $template->setValue('issueDate', $issueDate);
                $vessels = $company->vessels()->select('id', 'name')->where('active',1)->orderBy('name')->get();


                $columns = 4;
                $rows = ceil(count($vessels) / $columns);
                $template->cloneRow('c1VesselName', $rows);
                for ($row = 1; $row <= $rows; $row++) {
                    for ($col = 1; $col <= $columns; $col++) {
                        $index = $col - 1 + $columns * ($row - 1);
                        if ($index < count($vessels)) {
                            $name = ucwords(strtolower($vessels[$index]->name));
                            $template->setValue('c' . $col . 'VesselName#' . $row, htmlspecialchars(sanitize_data_for_doc($name), ENT_COMPAT, 'UTF-8'));
                        } else {
                            $template->setValue('c' . $col . 'VesselName#' . $row, '');
                        }
                    }
                }
                if($company->qi_id > 0){
                    $qi = Company::where('id',$company->qi_id)->first();
                    $template->setValue('QIs', sanitize_data_for_doc($qi->name));
                }else {
                    $template->setValue('QIs', 'None');
                }
                break;
            case 'single-vessel-pre-fire-plan-certification':
                $vessel = Vessel::where('id', request('vessel'))->first();
                $fileName = str_slug($company->name) . ' - VPFP Certification  - ' . $vessel->name . ' - ' . date('m-d-Y_h_ia');
                $address =  str_replace(array('\n', '\r'), '</w:t><w:br/><w:t>', request('address'));
                $address = preg_replace(array('~\R~u','~[\r\n]+~'), '</w:t><w:br/><w:t>', $address);
                $template->setValue('companyName',request('name'));
                $template->setValue('companyAddress',  $address);
                $template->setValue('issueDate', $issueDate);
                //print_r($vessel);exit();
                $template->setValue('vesselName', sanitize_data_for_doc($vessel->name));
                if($company->qi_id > 0){
                    $qi = Company::where('id',$company->qi_id)->first();
                    $template->setValue('QIs', sanitize_data_for_doc($qi->name));
                } else {
//                    $template->deleteBlock('COPY_TO_BLOCK');
                    $template->setValue('QIs', 'None');
                }
                break;
            case 'aa-vessel-specific':
                $vessel = Vessel::where('id', request('vessel'))->first();
                $fileName = str_slug($company->name) . ' - AA-Vessel Specific Page - ' . $vessel->name . ' - ' . date('m-d-Y_h_ia');
                $template->setValue('vesselName', sanitize_data_for_doc($vessel->name));
                $template->setValue('imo', $vessel->imo);
                if ($vessel->deck_area) {
                    $DeckSF = number_format($vessel->deck_area * 10.7639, 1);
                    $WaterFoam = number_format($vessel->deck_area * 10.7639 / 62.5, 1);
                    $template->setValue('DeckSF', $DeckSF);
                    $template->setValue('WaterFoam', $WaterFoam);
                } else {
                    $template->setValue('DeckSF', 'NA');
                    $template->setValue('WaterFoam', 'NA');
                }
                if ($vessel->oil_tank_volume) {
                    $LgstTank = number_format($vessel->oil_tank_volume * 264.172, 1);
                    $GPH = number_format(($vessel->oil_tank_volume * 264.172) / 24, 1);
                    $GPM = number_format(($vessel->oil_tank_volume * 264.172) / 1440, 1);
                    $template->setValue('LgstTank', $LgstTank);
                    $template->setValue('GPH', $GPH);
                    $template->setValue('GPM', $GPM);
                    $maxValue = 49300;
                    $PUMPS_MAP = [
                        2900 => [1, 1, 1, 1],
                        5800 => [1, 1, 2, 2],
                        8700 => [2, 2, 2, 3],
                        11600 => [2, 2, 3, 4],
                        14500 => [2, 3, 3, 5],
                        17400 => [3, 3, 4, 6],
                        20300 => [3, 3, 4, 7],
                        23200 => [3, 4, 5, 8],
                        26100 => [4, 4, 5, 9],
                        29000 => [4, 5, 6, 10],
                        31900 => [4, 5, 6, 11],
                        34800 => [5, 6, 7, 12],
                        37700 => [5, 6, 7, 13],
                        40600 => [5, 6, 8, 14],
                        43500 => [6, 7, 8, 15],
                        46400 => [6, 7, 9, 16],
                        49300 => [7, 8, 9, 17]
                    ];
                    $OIL_GROUPS = ['I', 'II', 'III', 'IV'];
                    $pumpMap = $PUMPS_MAP[$maxValue];
                    $idxOfOilGroup = array_search(strtoupper($vessel->oil_group), $OIL_GROUPS, true);
                    foreach ($PUMPS_MAP as $key => $value) {
                        if ($key < $maxValue && $this->cleanNumber($vessel->oil_tank_volume) < $key) {
                            $pumpMap = $value;
                            break;
                        }
                    }
                    $Pumps = $pumpMap[$idxOfOilGroup] ?? $pumpMap[3];
                    $template->setValue('Pumps', $Pumps);
                } else {
                    $template->setValue('LgstTank', 'NA');
                    $template->setValue('GPH', 'NA');
                    $template->setValue('GPM', 'NA');
                    $template->setValue('Pumps', 'NA');
                }
                if ($vessel->dead_weight) {
                    $template->setValue('DWT', $vessel->dead_weight);
                    $TUG_HP_MAP = [
                        25000 => 1000,
                        50000 => 1500,
                        75000 => 1500,
                        100000 => 2500,
                        125000 => 2500,
                        150000 => 3500,
                        175000 => 4500,
                        200000 => 4500,
                        225000 => 5500,
                        250000 => 6500,
                        500000 => 7500
                    ];
                    $TugHP = 7500;
                    foreach ($TUG_HP_MAP as $key => $value) {
                        if ($this->cleanNumber($vessel->dead_weight) <= (int)$key) {
                            $TugHP = $value;
                            break;
                        }
                    }
                    $template->setValue('TugHP', $TugHP);
                } else {
                    $template->setValue('DWT', 'NA');
                    $template->setValue('TugHP', 'NA');
                }
                $template->setValue('Oil_Group', $vessel->oil_group);
                $dsm = $vessel->vendors()->whereHas('type', function ($q) {
                    $q->where('name', 'Damage Stability Certificate Provider');
                })->first();
                if ($dsm) {
                    $template->setValue('DamageStabilityProvider', htmlspecialchars($dsm->name, ENT_COMPAT, 'UTF-8'));
                } else {
                    $template->setValue('DamageStabilityProvider', ' // ');
                }
                break;
        }
        $tmpFile = storage_path('documents/tmp/' . $fileName . $extension);
        $tmpFilePDF = storage_path('documents/tmp/' . $fileName . '.pdf');
        $template->saveAs($tmpFile);
        $directory = 'files/Documents/' . $company->id . '/' . $location . '/';

        shell_exec('export HOME=/tmp/ && /usr/bin/soffice --headless --convert-to pdf:writer_pdf_Export --outdir ' . storage_path('documents/tmp/') . ' \'' . $tmpFile . '\' 2>&1');

//        return response()->json($output);
        //        $process = new Process(['libreoffice', '--headless', '--convert-to', 'pdf', $tmpFile, '--outdir', storage_path('documents/tmp/')]);
//        $process = new Process(['unoconv', '-f', 'pdf', '-o', storage_path('documents/tmp/' . $fileName), $tmpFile]);
//        $process->setEnv(['TMPDIR' => '/tmp']);
//        $process->run();
//        shell_exec('sudo unoconv -f pdf -o ' . storage_path('documents/tmp/') . ' "' . $tmpFile . '"');
        if (Storage::disk('gcs')->putFileAs($directory, new File($tmpFile), $fileName . $extension)) {

            unlink($tmpFile);

            if (Storage::disk('gcs')->putFileAs($directory, new File($tmpFilePDF), $fileName . '.pdf')) {//$process->isSuccessful() &&
                unlink($tmpFilePDF);
            }
            return response()->json(['message' => 'File generated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
      }else{
            return response()->json(['message' => 'Related document does not exist.']);
      }
    }

    private function formatAddressMultiline($address, $use_fields)
    {
        $multilineAddress = $address->document_format;
        if ($use_fields || !$multilineAddress) {
            if ($address->street) {
                $multilineAddress = $address->street;
            }
            if ($address->unit) {
                $multilineAddress .= ', ' . $address->unit;
            }
            $multilineAddress .= "\n";
            if ($address->city) {
                $multilineAddress .= $address->city;
            }
            if ($address->province) {
                $multilineAddress .= ', ' . $address->province;
            }
            if ($address->zip) {
                $multilineAddress .= ', ' . $address->zip;
            }
            $multilineAddress .= "\n";
            if ($address->country) {
                $multilineAddress .= $address->country;
            }
        }
        return preg_replace('~\R~u', '</w:t><w:br/><w:t>', $multilineAddress);
    }

    private function cleanNumber($a): int
    {
        return (int)str_replace(',', '', $a);
    }
}
