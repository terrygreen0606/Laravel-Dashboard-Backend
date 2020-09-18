<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class WeatherController extends Controller
{
    const PARAMS = [
        'wind' => ['WIND'],
        'wave' => ['DIRPW', 'HTSGW', 'WVPER'],
        'tmp' => ['TMP'],
    ];

    public function getWeather($filter)
    {
        $gribFileNotExist = array('');
        $filter = json_decode($filter);
        $data = [];

        try {
            foreach (self::PARAMS[$filter->type] as $param) {
                $filepath = base_path() . '/storage/weather/' . $filter->datetime->date . '/' . $filter->datetime->hour . '-' . $param . '.grib2';

                if (file_exists($filepath)) {
                    $json = $this->getJsonFromGrib($filepath);
                    if ($json) {
                        $data = array_merge($data, $json);
                    }
                    else return response()->json([
                        'message' => 'grib2json conversion error'
                    ], 500);
                } else {
                    $gribFileNotExist[] = $param . ' ';
                }
            }

            if (count($gribFileNotExist) == 1) {
                return response()->json([
                    'data' => $data,
                    'success' => true,
                ]);
            } else {
                $gribFileNotExist[] = ' data not exist';
                $message = implode("", $gribFileNotExist);
                return response()->json(['message' => $message], 500);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    private function getJsonFromGrib($filepath)
    {
        //echo storage_path() . '/grib2json/grib2json --compact --data --names ' . $filepath;exit;
        // $filepath = base_path() . '/storage/weather/2020-07-01/15-WIND.grib2';
        if (env('APP_ENV') == 'local') {
            $process = Process::fromShellCommandline(
                // base_path() . '/storage/grib2json/grib2json--compact --data --names ' . $filepath
                storage_path() . '/grib2json/grib2json --compact --data --names ' . $filepath,null,null
            );
        } else {
            $process = Process::fromShellCommandline(
                'grib2json --compact --data --names ' . $filepath,
                null,
                ['JAVA_HOME' => '/usr/lib/jvm/java-8-openjdk-amd64/jre/bin/java']
            );
        }

        try {
            $process->mustRun();
            return json_decode($process->getOutput());
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
            return false;
        }
    }
}
