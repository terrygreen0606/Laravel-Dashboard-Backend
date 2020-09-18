<?php
ini_set('max_execution_time', 10000);

if (!function_exists('getGeoZoneID')) {

    /**
     * description
     *
     * @param $point_y
     * @param $point_x
     * @return |null
     */
    function getGeoZoneID($point_y, $point_x)
    {
        //CONUS
        if ($point_x <= -65.7 && $point_x >= -129.25  && $point_y <= 49 && $point_y >= 23.8) {
            //WEST COAST
            if ($point_x <= -104.05 && $point_x >= -129.25  && $point_y <= 49 && $point_y >= 30.5) {
                $zones = [
                    ['id' => 39, 'name' => 'Upper_Mississippi_River'],
                    ['id' => 6, 'name' => 'Corpus_Christi'],
                    ['id' => 31, 'name' => 'Puget_Sound'],
                    ['id' => 5, 'name' => 'Columbia_River'],
                    ['id' => 33, 'name' => 'San_Francisco'],
                    ['id' => 19, 'name' => 'Los_Angeles_Long_Beach'],
                    ['id' => 32, 'name' => 'San_Diego'],
                ];
                for ($i = 0; $i < count($zones); $i++) {
                    if (geoPolyTest($zones[$i]['name'], $point_y, $point_x))
                        return $zones[$i]['id'];
                }
                return null;
            }
            //SOUTH
            else if ($point_x <= -71.25 && $point_x >= -104.05  && $point_y <= 37 && $point_y >= 23.8){
                $zones = [
                    ['id' => 39, 'name' => 'Upper_Mississippi_River'],
                    ['id' => 6, 'name' => 'Corpus_Christi'],
                    ['id' => 13, 'name' => 'Houston_Galveston'],
                    ['id' => 29, 'name' => 'Port_Arthur'],
                    ['id' => 22, 'name' => 'Morgan_City'],
                    ['id' => 24, 'name' => 'New_Orleans'],
                    ['id' => 21, 'name' => 'Mobile'],
                    ['id' => 38, 'name' => 'St_Petersburg'],
                    ['id' => 15, 'name' => 'Key_West'],
                    ['id' => 21, 'name' => 'Miami'],
                    ['id' => 14, 'name' => 'Jacksonville'],
                    ['id' => 1, 'name' => 'MSU_Savannah'],
                    ['id' => 4, 'name' => 'Charleston'],
                    ['id' => 25, 'name' => 'North_Carolina'],
                    ['id' => 11, 'name' => 'Hampton_Roads'],
                    ['id' => 28, 'name' => 'Ohio_Valley'],
                    ['id' => 20, 'name' => 'Lower_Mississippi'],
                ];
                for ($i = 0; $i < count($zones); $i++) {
                    if (geoPolyTest($zones[$i]['name'], $point_y, $point_x))
                        return $zones[$i]['id'];
                }
                return null;
            }
            //NORTH AND LAKE
            else if($point_x <= -65.65 && $point_x >= -104.05  && $point_y <= 49.39 && $point_y >= 37){
                $zones = [
                    ['id' => 39, 'name' => 'Upper_Mississippi_River'],
                    ['id' => 28, 'name' => 'Ohio_Valley'],
                    ['id' => 11, 'name' => 'Hampton_Roads'],
                    ['id' => 2, 'name' => 'Baltimore'],
                    ['id' => 23, 'name' => 'MSU_Pittsburgh'],
                    ['id' => 7, 'name' => 'Delaware_Bay'],
                    ['id' => 25, 'name' => 'New_York'],
                    ['id' => 17, 'name' => 'Long_Island_Sound'],
                    ['id' => 37, 'name' => 'Southeastern_New_England'],
                    ['id' => 27, 'name' => 'Northern_New_England'],
                    ['id' => 3, 'name' => 'Boston'],
                    ['id' => 18, 'name' => 'Buffalo'],
                    ['id' => 8, 'name' => 'Detroit'],
                    ['id' => 16, 'name' => 'Lake_Michigan'],
                    ['id' => 35, 'name' => 'Sault_Ste_Marie'],
                    ['id' => 9, 'name' => 'Duluth'],
                ];
                for ($i = 0; $i < count($zones); $i++) {
                    if (geoPolyTest($zones[$i]['name'], $point_y, $point_x))
                        return $zones[$i]['id'];
                }
                return null;
            }
            //OUTSIDE EEZ
            else{
                return null;
            }
        }
        //SAN JUAN
        else if($point_x <= -63.85 && $point_x >= -68.55  && $point_y <=  21.9 && $point_y >= 14.9){
            if (geoPolyTest('San_Juan', $point_y, $point_x))
                return 34;
            return null;
        }
        //Alaska
        else if($point_x >= -180 && $point_x <= -130  && $point_y <=  75 && $point_y >= 47.8){
            $zones = [
                ['id' => 36, 'name' => 'Southeast_Alaska'],
                ['id' => 30, 'name' => 'Prince_William_Sound'],
                ['id' => 40, 'name' => 'Western_Alaska'],
            ];
            for ($i = 0; $i < count($zones); $i++) {
                if (geoPolyTest($zones[$i]['name'], $point_y, $point_x))
                    return $zones[$i]['id'];
            }
            return null;
        }
        //Western Alaska
        else if($point_x <= 180 && $point_x >= 167.5  && $point_y <= 60.1 && $point_y >= 47.8){
            if (geoPolyTest('Western_Alaska', $point_y, $point_x))
                return 40;
            return null;
        }
        //Honolulu
        else if(
            ($point_x <= -151.25 && $point_x >= -180    && $point_y <= 31.9 && $point_y >= 0) ||
            ($point_x <= -157.4 && $point_x >= -180  && $point_y <=  0 && $point_y >= -3.75) ||
            ($point_x <= 170.25 && $point_x >= 163.25  && $point_y <= 22.7 && $point_y >= 16.25) ||
            ($point_x <= 180 && $point_x >= 177.8 && $point_y <= 31.5 && $point_y >= 25.35)
        ){
            if (geoPolyTest('Honolulu', $point_y, $point_x))
                return 12;
            return null;
        }else if($point_x <= 149.75 && $point_x >= 141.15    && $point_y <= 23.95 && $point_y >= 10.9){
            if (geoPolyTest('Guam', $point_y, $point_x))
                return 10;
            return null;
        }
        return null;
    }
}

if (!function_exists('geoPolyTest')) {
    // General Polygon Code
    function geoPolyTest($zoneName, $point_y, $point_x): bool
    {
        $zones = json_decode(file_get_contents(base_path() . '/storage/zone_tests/index.json'));
        $eps = 1e-4;
        if (abs($point_y) < $eps) $point_y = $eps;
        if (abs($point_x) < $eps) $point_x = $eps;

        $polyXYZ = [];
        for ($i = 0; $i < count($zones); $i++) {
            if ($zones[$i]->name == $zoneName) $polyXYZ = $zones[$i]->points;
        }

        $insideXYZ = false;
        for ($i = 0, $j = \count($polyXYZ) - 1, $iMax = \count($polyXYZ); $i < $iMax; $j = $i++) {
            if ((($polyXYZ[$i]->y > $point_y) !== ($polyXYZ[$j]->y > $point_y)) && ($point_x <= ($polyXYZ[$j]->x - $polyXYZ[$i]->x) * ($point_y - $polyXYZ[$i]->y) / ($polyXYZ[$j]->y - $polyXYZ[$i]->y) + $polyXYZ[$i]->x)) {
                $insideXYZ = !$insideXYZ;
            }
        }
        return $insideXYZ;
    }
}

if (!function_exists('code_to_country')) {

    function codeToCountryToCode ($code)
    {

        $code = strtoupper($code);

        $countryList = array(
            'AF' => 'Afghanistan',
            'AX' => 'Aland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas the',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island (Bouvetoya)',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory (Chagos Archipelago)',
            'VG' => 'British Virgin Islands',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros the',
            'CD' => 'Congo',
            'CG' => 'Congo the',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote d\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FO' => 'Faroe Islands',
            'FK' => 'Falkland Islands (Malvinas)',
            'FJ' => 'Fiji the Fiji Islands',
            'FI' => 'Finland',
            'FR' => 'France, French Republic',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia the',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and McDonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea',
            'KR' => 'Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyz Republic',
            'LA' => 'Lao',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'AN' => 'Netherlands Antilles',
            'NL' => 'Netherlands the',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn Islands',
            'PL' => 'Poland',
            'PT' => 'Portugal, Portuguese Republic',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthelemy',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia (Slovak Republic)',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia, Somali Republic',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard & Jan Mayen Islands',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland, Swiss Confederation',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States of America',
            'UM' => 'United States Minor Outlying Islands',
            'VI' => 'United States Virgin Islands',
            'UY' => 'Uruguay, Eastern Republic of',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
            'USA' => 'US'
        );

        $countryListReverse = [];
        $countryListClone = [];

        foreach ($countryList as $key => $value) {
            $countryListClone[strtolower($key)] = strtolower($value);
        }

        $countryList = $countryListClone;

        foreach ($countryList as $key => $value) {
            $countryListReverse[$value] = $key;
        }

        return !isset($countryList[strtolower($code)]) ? (!isset($countryListReverse[strtolower($code)]) ? $code : strtoupper($countryListReverse[strtolower($code)])) : strtoupper($countryList[strtolower($code)]);
    }
}

if (!function_exists('CodeCountryByCountryName')) {
        function CodeCountryByCountryName($name){
            $countryList = array(
                'AF' => 'Afghanistan',
                'AX' => 'Aland Islands',
                'AL' => 'Albania',
                'DZ' => 'Algeria',
                'AS' => 'American Samoa',
                'AD' => 'Andorra',
                'AO' => 'Angola',
                'AI' => 'Anguilla',
                'AQ' => 'Antarctica',
                'AG' => 'Antigua and Barbuda',
                'AR' => 'Argentina',
                'AM' => 'Armenia',
                'AW' => 'Aruba',
                'AU' => 'Australia',
                'AT' => 'Austria',
                'AZ' => 'Azerbaijan',
                'BS' => 'Bahamas the',
                'BH' => 'Bahrain',
                'BD' => 'Bangladesh',
                'BB' => 'Barbados',
                'BY' => 'Belarus',
                'BE' => 'Belgium',
                'BZ' => 'Belize',
                'BJ' => 'Benin',
                'BM' => 'Bermuda',
                'BT' => 'Bhutan',
                'BO' => 'Bolivia',
                'BA' => 'Bosnia and Herzegovina',
                'BW' => 'Botswana',
                'BV' => 'Bouvet Island (Bouvetoya)',
                'BR' => 'Brazil',
                'IO' => 'British Indian Ocean Territory (Chagos Archipelago)',
                'VG' => 'British Virgin Islands',
                'BN' => 'Brunei Darussalam',
                'BG' => 'Bulgaria',
                'BF' => 'Burkina Faso',
                'BI' => 'Burundi',
                'KH' => 'Cambodia',
                'CM' => 'Cameroon',
                'CA' => 'Canada',
                'CV' => 'Cape Verde',
                'KY' => 'Cayman Islands',
                'CF' => 'Central African Republic',
                'TD' => 'Chad',
                'CL' => 'Chile',
                'CN' => 'China',
                'CX' => 'Christmas Island',
                'CC' => 'Cocos (Keeling) Islands',
                'CO' => 'Colombia',
                'KM' => 'Comoros the',
                'CD' => 'Congo',
                'CG' => 'Congo the',
                'CK' => 'Cook Islands',
                'CR' => 'Costa Rica',
                'CI' => 'Cote d\'Ivoire',
                'HR' => 'Croatia',
                'CU' => 'Cuba',
                'CY' => 'Cyprus',
                'CZ' => 'Czech Republic',
                'DK' => 'Denmark',
                'DJ' => 'Djibouti',
                'DM' => 'Dominica',
                'DO' => 'Dominican Republic',
                'EC' => 'Ecuador',
                'EG' => 'Egypt',
                'SV' => 'El Salvador',
                'GQ' => 'Equatorial Guinea',
                'ER' => 'Eritrea',
                'EE' => 'Estonia',
                'ET' => 'Ethiopia',
                'FO' => 'Faroe Islands',
                'FK' => 'Falkland Islands (Malvinas)',
                'FJ' => 'Fiji the Fiji Islands',
                'FI' => 'Finland',
                'FR' => 'France, French Republic',
                'GF' => 'French Guiana',
                'PF' => 'French Polynesia',
                'TF' => 'French Southern Territories',
                'GA' => 'Gabon',
                'GM' => 'Gambia the',
                'GE' => 'Georgia',
                'DE' => 'Germany',
                'GH' => 'Ghana',
                'GI' => 'Gibraltar',
                'GR' => 'Greece',
                'GL' => 'Greenland',
                'GD' => 'Grenada',
                'GP' => 'Guadeloupe',
                'GU' => 'Guam',
                'GT' => 'Guatemala',
                'GG' => 'Guernsey',
                'GN' => 'Guinea',
                'GW' => 'Guinea-Bissau',
                'GY' => 'Guyana',
                'HT' => 'Haiti',
                'HM' => 'Heard Island and McDonald Islands',
                'VA' => 'Holy See (Vatican City State)',
                'HN' => 'Honduras',
                'HK' => 'Hong Kong',
                'HU' => 'Hungary',
                'IS' => 'Iceland',
                'IN' => 'India',
                'ID' => 'Indonesia',
                'IR' => 'Iran',
                'IQ' => 'Iraq',
                'IE' => 'Ireland',
                'IM' => 'Isle of Man',
                'IL' => 'Israel',
                'IT' => 'Italy',
                'JM' => 'Jamaica',
                'JP' => 'Japan',
                'JE' => 'Jersey',
                'JO' => 'Jordan',
                'KZ' => 'Kazakhstan',
                'KE' => 'Kenya',
                'KI' => 'Kiribati',
                'KP' => 'Korea',
                'KR' => 'Korea',
                'KW' => 'Kuwait',
                'KG' => 'Kyrgyz Republic',
                'LA' => 'Lao',
                'LV' => 'Latvia',
                'LB' => 'Lebanon',
                'LS' => 'Lesotho',
                'LR' => 'Liberia',
                'LY' => 'Libyan Arab Jamahiriya',
                'LI' => 'Liechtenstein',
                'LT' => 'Lithuania',
                'LU' => 'Luxembourg',
                'MO' => 'Macao',
                'MK' => 'Macedonia',
                'MG' => 'Madagascar',
                'MW' => 'Malawi',
                'MY' => 'Malaysia',
                'MV' => 'Maldives',
                'ML' => 'Mali',
                'MT' => 'Malta',
                'MH' => 'Marshall Islands',
                'MQ' => 'Martinique',
                'MR' => 'Mauritania',
                'MU' => 'Mauritius',
                'NL' => 'The Netherlands',
                'YT' => 'Mayotte',
                'MX' => 'Mexico',
                'FM' => 'Micronesia',
                'MD' => 'Moldova',
                'MC' => 'Monaco',
                'MN' => 'Mongolia',
                'ME' => 'Montenegro',
                'MS' => 'Montserrat',
                'MA' => 'Morocco',
                'MZ' => 'Mozambique',
                'MM' => 'Myanmar',
                'NA' => 'Namibia',
                'NR' => 'Nauru',
                'NP' => 'Nepal',
                'AN' => 'Netherlands Antilles',
                'NL' => 'The Netherlands',
                'NC' => 'New Caledonia',
                'NZ' => 'New Zealand',
                'NI' => 'Nicaragua',
                'NE' => 'Niger',
                'NG' => 'Nigeria',
                'NU' => 'Niue',
                'NF' => 'Norfolk Island',
                'MP' => 'Northern Mariana Islands',
                'NO' => 'Norway',
                'OM' => 'Oman',
                'PK' => 'Pakistan',
                'PW' => 'Palau',
                'PS' => 'Palestinian Territory',
                'PA' => 'Panama',
                'PG' => 'Papua New Guinea',
                'PY' => 'Paraguay',
                'PE' => 'Peru',
                'PH' => 'Philippines',
                'PN' => 'Pitcairn Islands',
                'PL' => 'Poland',
                'PT' => 'Portugal, Portuguese Republic',
                'RU' => 'Russia',
                'PR' => 'Puerto Rico',
                'QA' => 'Qatar',
                'RE' => 'Reunion',
                'RO' => 'Romania',
                'RU' => 'Russian Federation',
                'RW' => 'Rwanda',
                'BL' => 'Saint Barthelemy',
                'SH' => 'Saint Helena',
                'KN' => 'Saint Kitts and Nevis',
                'LC' => 'Saint Lucia',
                'MF' => 'Saint Martin',
                'PM' => 'Saint Pierre and Miquelon',
                'VC' => 'Saint Vincent and the Grenadines',
                'WS' => 'Samoa',
                'SM' => 'San Marino',
                'ST' => 'Sao Tome and Principe',
                'SA' => 'Saudi Arabia',
                'SN' => 'Senegal',
                'RS' => 'Serbia',
                'SC' => 'Seychelles',
                'SL' => 'Sierra Leone',
                'SG' => 'Singapore',
                'SK' => 'Slovakia (Slovak Republic)',
                'SI' => 'Slovenia',
                'SB' => 'Solomon Islands',
                'SO' => 'Somalia, Somali Republic',
                'ZA' => 'South Africa',
                'GS' => 'South Georgia and the South Sandwich Islands',
                'ES' => 'Spain',
                'LK' => 'Sri Lanka',
                'SD' => 'Sudan',
                'SR' => 'Suriname',
                'SJ' => 'Svalbard & Jan Mayen Islands',
                'SZ' => 'Swaziland',
                'SE' => 'Sweden',
                'CH' => 'Switzerland, Swiss Confederation',
                'SY' => 'Syrian Arab Republic',
                'TW' => 'Taiwan',
                'TJ' => 'Tajikistan',
                'TZ' => 'Tanzania',
                'TH' => 'Thailand',
                'TL' => 'Timor-Leste',
                'TG' => 'Togo',
                'TK' => 'Tokelau',
                'TO' => 'Tonga',
                'TT' => 'Trinidad and Tobago',
                'TN' => 'Tunisia',
                'TR' => 'Turkey',
                'TM' => 'Turkmenistan',
                'TC' => 'Turks and Caicos Islands',
                'TV' => 'Tuvalu',
                'UG' => 'Uganda',
                'UA' => 'Ukraine',
                'AE' => 'United Arab Emirates',
                'GB' => 'United Kingdom',
                'US' => 'United States of America',
                'UM' => 'United States Minor Outlying Islands',
                'VI' => 'United States Virgin Islands',
                'UY' => 'Uruguay, Eastern Republic of',
                'UZ' => 'Uzbekistan',
                'VU' => 'Vanuatu',
                'VE' => 'Venezuela',
                'VN' => 'Vietnam',
                'WF' => 'Wallis and Futuna',
                'EH' => 'Western Sahara',
                'YE' => 'Yemen',
                'ZM' => 'Zambia',
                'ZW' => 'Zimbabwe',
                'USA' => 'US'
            );

            return array_search($name,$countryList) ? array_search($name,$countryList) : null;
        }

    }
