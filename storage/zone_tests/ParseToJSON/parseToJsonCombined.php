<?php
/**
 * Created by PhpStorm.
 * User: darko
 * Date: 3/26/19
 * Time: 8:32 PM
 */
$files = scandir('./', SCANDIR_SORT_NONE);
foreach ($files as $file) {
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'geojson') {
        $json = json_decode(file_get_contents($file));
        
        foreach ($json->features as $feature) {
        	$export_coordinates = [];
            foreach ($feature->geometry->coordinates as $coordinate) {
                $export_coordinates[] = ['x' => 0, 'y' => 0];//open polygon
                foreach ($coordinate as $xy) {
                    if (!is_array($xy[0])) {
                        $x = $xy[0];
                        $y = $xy[1];
                        $export_coordinates[] = [
                            'x' => $x,
                            'y' => $y
                        ];
                    } else {
                        foreach ($xy as $item) {
                            $x = $item[0];
                            $y = $item[1];
                            $export_coordinates[] = [
                                'x' => $x,
                                'y' => $y
                            ];
                        }
                    }
                }
                $export_coordinates[] = ['x' => 0, 'y' => 0];//close the polygon
            }
        $new_name = $feature->properties->ZoneName . '.json';
        $fp = fopen($new_name, 'w');
        fwrite($fp, json_encode($export_coordinates));
        fclose($fp);
        }
    }
}