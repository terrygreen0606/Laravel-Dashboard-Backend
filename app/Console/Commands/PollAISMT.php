<?php

namespace App\Console\Commands;

use App\Helpers\MT_API_Helper;
use App\Models\VesselAISMTPoll;
use App\Models\Alert;
use App\Models\Network;
use App\Models\Fleet;
use DateTime;

use Illuminate\Console\Command;

class PollAISMT extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:poll-ais-mt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for vessel AIS MT polls that need running and run';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $nowTime = date("Y-m-d H:i:s");
        echo "Starting point" . $nowTime . "\n";
        $contVesselCount = VesselAISMTPoll::count();
        echo "\nTotal Vessels are " . $contVesselCount . "\n\n";
        if($contVesselCount > 0) {
            $vessel_ais_datas = VesselAISMTPoll::where('last_update', '<', $nowTime)->get();
            foreach($vessel_ais_datas as $vessel_ais_data) {
                $ais_alert = Alert::where('categories', 'ais_status');
                $ais_response = MT_API_Helper::getAIS_PS07($vessel_ais_data->vessel_id, $vessel_ais_data->satellite, $vessel_ais_data->extended);
                if ($ais_response['success']) {
                    $updateTime = date('Y-m-d H:i:s', strtotime('+' . $vessel_ais_data->interval . 'minutes', strtotime($nowTime)));
                    VesselAISMTPoll::where('vessel_id', $vessel_ais_data->vessel_id)->update(['last_update' => $updateTime]);
                    if ($ais_alert->first()) {
                        $ais_alert->update(['active' => 0]);
                    }
                } else {
                    if ($ais_alert->first()) {
                        $ais_alert->update(['active' => 1]);
                    } else {
                        Alert::create([
                            'contents' => $ais_response['message'],
                            'categories' => 'ais_status',
                            'active' => 1
                        ]);
                    }
                    VesselAISMTPoll::truncate();
                    Network::where('ter_status', 1)->update(['ter_status' => 0]);
                    Network::where('sat_status', 1)->update(['sat_status' => 0]);
                    Fleet::where('ter_status', 1)->update(['ter_status' => 0]);
                    Fleet::where('sat_status', 1)->update(['sat_status' => 0]);
                    break;
                }
            }
        }
        echo "Action finished. \n";

        // For local Testing
        // while(true)
        // {
        //     $nowTime = date("Y-m-d H:i:s");
        //     $contVesselCount = VesselAISMTPoll::count();
        //     if($contVesselCount > 0) {
        //         $vessel_ais_datas = VesselAISMTPoll::where('last_update', '<', $nowTime)->get();
        //         foreach($vessel_ais_datas as $vessel_ais_data) {
        //             $ais_alert = Alert::where('categories', 'ais_status');
        //             $ais_response = MT_API_Helper::getAIS_PS07($vessel_ais_data->id, $vessel_ais_data->satellite, $vessel_ais_data->extended);
        //             if ($ais_response['success']) {
        //                 $updateTime = date('Y-m-d H:i:s', strtotime('+' . $vessel_ais_data->interval . 'minutes', strtotime($nowTime)));
        //                 VesselAISMTPoll::where('vessel_id', $vessel_ais_data->vessel_id)->update(['last_update' => $updateTime]);
        //                 if ($ais_alert->first()) {
        //                     $ais_alert->update(['active' => 0]);
        //                 }
        //             } else {
        //                 if ($ais_alert->first()) {
        //                     $ais_alert->update(['active' => 1]);
        //                 } else {
        //                     Alert::create([
        //                         'contents' => $ais_response['message'],
        //                         'categories' => 'ais_status',
        //                         'active' => 1
        //                     ]);
        //                 }
        //                 VesselAISMTPoll::where('vessel_id', $vessel_ais_data->vessel_id)->delete();
        //             }
        //         }
        //     }
        //     sleep(60);
        // }
    }

}
