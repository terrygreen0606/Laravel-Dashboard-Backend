<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GetWeatherInfo extends Command
{
    const DUMMY_WIND_TMP_URL = 'https://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_0p25_1hr.pl?file=gfs.t12z.pgrb2.0p25.f002&lev_2_m_above_ground=on&lev_surface=on&var_TMP=on&leftlon=0&rightlon=360&toplat=90&bottomlat=-90&dir=%2Fgfs.20200624%2F12';

    const DUMMY_WAVE_URL = 'ftp://ftpprd.ncep.noaa.gov/pub/data/nccf/com/wave/prod/multi_1.20200623/multi_1.glo_30m.t00z.f010.grib2';

    const WIND_URL = 'https://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_0p25_1hr.pl?file=gfs.t{6HOUR}z.pgrb2.0p25.f{+HOUR}&lev_10_m_above_ground=on&lev_surface=on&var_UGRD=on&var_VGRD=on&leftlon=0&rightlon=360&toplat=90&bottomlat=-90&dir=%2Fgfs.{DATE}%2F{6HOUR}';

    const TMP_URL = 'https://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_0p25_1hr.pl?file=gfs.t{6HOUR}z.pgrb2.0p25.f{+HOUR}&lev_2_m_above_ground=on&lev_surface=on&var_TMP=on&leftlon=0&rightlon=360&toplat=90&bottomlat=-90&dir=%2Fgfs.{DATE}%2F{6HOUR}';

    const WAVE_URL = 'ftp://ftpprd.ncep.noaa.gov/pub/data/nccf/com/wave/prod/multi_1.{DATE}/multi_1.glo_30m.t{6HOUR}z.f{+HOUR}.grib2';

    const WAVE_TYPES = [
        ['HTSGW', 5],
        ['DIRPW', 7],
        ['WVPER', 11],
    ];

    const FORECAST_LIMIT_HOURS = 120;
    const HINDCAST_LIMIT_DAYS = 3;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:weather-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Weather Information from API(NOAA, WaveIII)';

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
        $now = Carbon::now();
        $this->getDataFromGFS(self::WIND_URL, clone($now), 'WIND');
        $this->getDataFromGFS(self::TMP_URL, clone($now), 'TMP');
        $this->getDataFromWave(self::WAVE_URL, clone($now));
        $this->removeHindcastDirectory($now->subDays(self::HINDCAST_LIMIT_DAYS + 1));
    }

    /**
     * get wind and temperature grib files from service
     *
     * @param string $baseUrl
     * @param datetime $dateTime
     * @param string $type 'WIND' || 'TMP'
     * @return void
     */
    private function getDataFromGFS($baseUrl, $dateTime, $type)
    {
        $baseUrl = str_replace('{DATE}', $this->getDate($dateTime), $baseUrl);
        $baseUrl = str_replace('{6HOUR}', $this->get6hours($dateTime), $baseUrl);

        $dateTime->hour = $this->get6hours($dateTime);

        for ($i = 0; $i <= self::FORECAST_LIMIT_HOURS; $i++) {
            $url = str_replace('{+HOUR}', $this->fixedLength($i, 3), $baseUrl);

            if ($i) $dateTime->addHour();

            $dirPath = base_path() . '/storage/weather/' . $dateTime->toDateString();
            $this->createDirectory($dirPath);

            $hour = $this->fixedLength($dateTime->hour);
            $filePath = $dirPath . '/' . $hour . '-' . $type . '.grib2';

            // echo 'i ' . $i . PHP_EOL;
            // echo 'file ' . $filePath . PHP_EOL;
            // echo 'url ' . $url . PHP_EOL;
            // echo PHP_EOL;

            try {
                $gribContents = file_get_contents($url);
                $fp = fopen($filePath, 'wb');
                fwrite($fp, $gribContents);
                fclose($fp);
            } catch (\Exception $exception) {
                echo $exception->getMessage();
                return;
            }
        }
    }

    private function getDataFromWave($baseUrl, $dateTime)
    {
        $baseUrl = str_replace('{DATE}', $this->getDate($dateTime), $baseUrl);
        $baseUrl = str_replace('{6HOUR}', $this->get6hours($dateTime), $baseUrl);

        $dateTime->hour = $this->get6hours($dateTime);

        for ($i = 0; $i <= self::FORECAST_LIMIT_HOURS; $i++) {
            $url = str_replace('{+HOUR}', $this->fixedLength($i, 3), $baseUrl);

            if ($i) $dateTime->addHour();

            $dirPath = base_path() . '/storage/weather/' . $dateTime->toDateString();
            $this->createDirectory($dirPath);

            $hour = $this->fixedLength($dateTime->hour);

            $gribIdx = file_get_contents($url . '.idx');
            // echo $gribIdx . PHP_EOL;
            // echo 'url ' . $url . PHP_EOL;

            for ($j = 0; $j < count(self::WAVE_TYPES); $j++) {
                $st = $this->getOffset($gribIdx, self::WAVE_TYPES[$j][1]);
                $en = $this->getOffset($gribIdx, self::WAVE_TYPES[$j][1] + 1);
                $filePath = $dirPath . '/' . $hour . '-' . self::WAVE_TYPES[$j][0] . '.grib2';

                // echo 'i ' . $i . PHP_EOL;
                // echo 'file ' . $filePath . PHP_EOL;
                // echo 'url ' . $url . PHP_EOL;
                // echo PHP_EOL;

                // echo $st . ' - '. $en . PHP_EOL;

                $process = Process::fromShellCommandline(
                    'curl -f -v -r ' . $st . '-' . 
                    ($en >= 0 ? ($en - 1) :  '') .
                    ' ' . $url .
                    ' -o ' . $filePath
                );

                try {
                    $process->mustRun();
                } catch (ProcessFailedException $exception) {
                    echo $exception->getMessage();
                    return;
                }
            }
        }
    }

    /**
     * date as '20200627' format
     *
     * @param datetime $datetime
     * @return string
     */
    private function getDate($datetime)
    {
        return $datetime->year .
            $this->fixedLength($datetime->month) .
            $this->fixedLength($datetime->day);
    }

    /**
     * 00 || 06 || 12 || 18
     *
     * @param Carbon::datetime $datetime
     * @return string
     */
    private function get6hours($datetime)
    {
        return $this->fixedLength(6 * floor($datetime->hour / 6));
    }

    /**
     * return fixed length integer->string
     * 3 -> '03', if $length = 2
     * 3 -> '003', if $length = 3
     *
     * @param integer $value
     * @param integer $length
     * @return string
     */
    private function fixedLength($value, $length = 2)
    {
        $valueLength = $value > 1 ? ceil(log10($value) + 0.000001) : 1;
        $prefix = str_repeat('0', $length - $valueLength);
        return $prefix . $value;
    }

    /**
     * if directory not exist, create it
     *
     * @param string $dirPath
     * @return void
     */
    private function createDirectory($dirPath)
    {
        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath);
        }
    }

    /**
     * if $id == 1 -> return 0
     * else if exist "\n{$id}" -> return offset
     * else -> return -1
     *
     * @param string $gribIdx
     * @param integer $id
     * @return integer
     */
    private function getOffset($gribIdx, $id)
    {
        if ($id == 1) return 0;
        $str = "\n" . $id . ":";
        $pos = strpos($gribIdx, $str);
        if (gettype($pos) == 'boolean' && !$pos) {
            return -1;
        } else {
            $st = $pos + strlen($str);
            $en = strpos($gribIdx, ':', $st);
            return (int) substr($gribIdx, $st, $en - $st);
        }
    }

    private function removeHindcastDirectory($datetime)
    {
        File::deleteDirectory(base_path() . '/storage/weather/' . $datetime->toDateString());
    }
}
