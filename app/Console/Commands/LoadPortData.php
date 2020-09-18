<?php

namespace App\Console\Commands;

use App\Models\Port;
use DateTime;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Storage;

class LoadPortData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:load-ports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load port data from CSV';

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

        $filename = storage_path('app/code-list_csv.csv');
        $file = fopen($filename, "r");
        while (($data = fgetcsv($file, 0, ',')) !== FALSE) {
            echo $data[1] . "\n";
            // 3519S 14804E
            $port_function = $data[6];
            $port_function = intval(preg_replace("/[^0-9]/", "", $port_function));
            $coords = $data[9];
            $lat = null;
            $long = null;
            if (!empty($coords) && strlen($coords) > 6) {
                $coords = explode(' ', $coords);
                $lat = $coords[0];
                $long = $coords[1];
    /*
    NW:
    46.96416°
    Longitude: -123.8532°
    3559N 07956W
    SE:
    Latitude: -32.9196°
    Longitude: 151.7688°
    3605S 14655E
    */
                $s = stripos($lat, "S") !== false;
                $w = stripos($long, "W") !== false;

                $lat = intval(preg_replace("/[^0-9]/", "", $lat));
                $long = intval(preg_replace("/[^0-9]/", "", $long));

                $lat = $lat / 100;
                $long = $long / 100;

                if ($s) $lat = $lat * -1;
                if ($w) $long = $long * -1;
            }
            Port::create([
                'locode_id' => $data[1],
                'country' => $data[0],
                'unlocode' => $data[0] . $data[1],
                'name' => $data[2],
                'name_ascii' => $data[3],
                'subdivision' => $data[4],
                'status' => $data[5],
                'port_function' => $port_function,
                'date_open' => $data[7],
                'iata' => $data[8],
                'latitude' => $lat,
                'longitude' => $long,
                'description' => $data[10],

            ]);
        }
    }

}
