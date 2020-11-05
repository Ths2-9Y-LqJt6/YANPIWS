<?php
/**
 * get all the valid config options required to bootstrap YANPIWS
 *
 * @return array of key names
 */
function getValidConfigs(){
    return array(
        'lat',
        'lon',
        'darksky',
        'labels',
        'animate',
        'dataPath',
        'api_password',
        'temp_count',
        'font_time_date_wind',
        'font_temp',
        'font_temp_label',
        // we accept these two. listing it here commented out for completeness. see getConfig() below
        // servers_*
        // labels_*
    );
}
/**
 * include the config file
 * @param boolean $die prints error and exits if fails
 */
function getConfig($die = true)
{
    if(is_file('../config.csv')) {
        global $YANPIWS ;
        $options = getValidConfigs();
        $YANPIWStmp = array_map('str_getcsv', file('../config.csv'));
        foreach ($YANPIWStmp as $config){

            if (substr($config[0],0,6) === 'labels'){
                $label = explode('_',$config[0]);
                $YANPIWS['labels'][$label[1]] = $config[1];
            } elseif(substr($config[0],0,7) === 'servers'){
                $serversAry = explode('_',$config[0]);
                $YANPIWS['servers'][$serversAry[1]][$serversAry[2]] = $config[1];
            } elseif (in_array($config[0],$options)) {
                $YANPIWS[$config[0]] = $config[1];
            }

        }
        $YANPIWS['cache_bust'] = '0.9.10';
    } elseif ($die) {
        die(
            '<h3>Error</h3><p>No config.csv!  Copy config.dist.csv to config.csv</p>'.
            getDailyForecastHtml()
        );
    }
}

/**
 * based on the values retrieved in getConfigOrDie(), validate all the config options
 *
 * @return array returns an array with key of valid as boolean and reason a string of why it's not valid
 */
function configIsValid($validateApi = false)
{
    global $YANPIWS;
    $valid = array('valid' => true, 'reason' => '');
    $options = getValidConfigs();
    if (sizeof($YANPIWS) < sizeof($options)){
        $valid['valid'] = false;
        $valid['reason'] .= 'Missing required option. ';
    }
    if (!isset($YANPIWS['darksky']) || strlen($YANPIWS['darksky']) != 32){
        $valid['valid'] = false;
        $valid['reason'] .= 'Dark Sky API Key is wrong length or missing. ';
    }
    if(!isset($YANPIWS['lat'])) {
        $valid['valid'] = false;
        $valid['reason'] .= 'Latitude is missing. ';
    } elseif (!validateLatitude($YANPIWS['lat'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Latitude is invalid. ';
    }
    if (!isset($YANPIWS['lon'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Longitude is missing. ';
    } elseif (!validateLongitude($YANPIWS['lon'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Longitude is invalid. ';
    }
    if (!isset($YANPIWS['temp_count'])){
        error_log('temp_count is unset! Defaulting to "1"');
        $YANPIWS['temp_count'] = 1;
    }

    // for these font sizes ones, lets default to a sane size
    // and then write and error to the error log. Will make
    // a much safer upgrade path for Manny ;)
    if (!isset($YANPIWS['font_time_date_wind'])){
        error_log('font_time_date_wind is unset! Defaulting to "35"');
        $YANPIWS['font_time_date_wind'] = 35;
    } elseif (!validateFontSize($YANPIWS['font_time_date_wind'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for time/date/wind is invalid. ';
    }
    if (!isset($YANPIWS['font_temp'])){
        error_log('font_temp is unset! Defaulting to "50"');
        $YANPIWS['font_temp'] = 50;
    } elseif (!validateFontSize($YANPIWS['font_temp'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for temp is invalid. ';
    }
    if (!isset($YANPIWS['font_temp_label'])){
        error_log('font_temp_label is unset! Defaulting to "25"');
        $YANPIWS['font_temp_label'] = 25;
    } elseif (!validateFontSize($YANPIWS['font_temp_label'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'Font size for temp label is invalid. ';
    }

    if (!isset($YANPIWS['dataPath']) || !is_writable($YANPIWS['dataPath'])){
        $valid['valid'] = false;
        $valid['reason'] .= 'DataPath does not exist or is not writable. ';
    }
    if ($validateApi){
        $http = curl_init(getDarkSkyUrl(true));
        curl_setopt($http, CURLOPT_NOBODY  , true);
        curl_exec($http);
        $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
        if ($http_status != 200){
            $valid['valid'] = false;
            $valid['reason'] .= 'Dark Sky API call failed: Either invalid API key or invalid Lat/Long ' .
                "(status: $http_status). ";
        }
    }
    return $valid;
}

/**
 * Assuming a CSV of this structure:
 *  2017-03-22 23:11:43,211,72.5,34
 * Return a multi-dimensional array like this:
 *
 *Array(
 *    [211] => Array(
 *        [2017-03-22 23:08:31] => Array(
 *                    [0] => 2017-03-22 23:08:31
 *                    [1] => 211
 *                    [2] => 72.5
 *                    [3] => 34
 *               )
 *
 * @param  string $file where CSV data is
 * @return array of formatted CSV data
 */
function getData($file)
{
    if (is_file($file) && is_readable($file)) {
        $data = file($file);
        $goodData = array();
        foreach ($data as $line){
            $lineArray = explode(",", $line);
            if(isset($lineArray[1])){
                $goodData[$lineArray[1]][$lineArray[0]] = $lineArray;
            }
        }
        foreach (array_keys($goodData) as $id){
            asort($goodData[$id]);
        }

        return $goodData;
    } else {
        return array();
    }
}

/**
 * @param $data1
 * @param $data2
 * @param $ids
 * @return array
 */
function mergeDayData($data1, $data2, $ids){
    $result = array();
    foreach ($ids as $id => $label) {
        if (isset($data1[$id]) && is_array($data1[$id])){
            foreach ($data1[$id] as $date => $temps) {
                $result[$id][$date] = $temps;
            }
        }
        if (isset($data2[$id]) && is_array($data2[$id])){
            foreach ($data2[$id] as $date => $temps) {
                $result[$id][$date] = $temps;
            }
        }
    }
    return $result;
}
/**
 * given the result from getData(), format the temps into averages by hour for last 24 hours.
 * @param $data array from getData()
 * @return array of up to 24 temps, 1 average per hour in log() scale
 */
function convertDataToHourly($data){
    $result = array();
    $counts = array();
    foreach ($data as $tempArray){
        $epoch = strtotime($tempArray[0]);
        $hour = date('G', $epoch);
        if(!isset($result[$hour])){
            $result[$hour] = 0;
            $counts[$hour] = 0;
        }
        $result[$hour] = $tempArray[2] + $result[$hour];
        $counts[$hour]++;
    }
    foreach ($counts as $hour => $count){
        $result[$hour] = $result[$hour]/$counts[$hour];
    }
//    die('daily temps:'.print_r($result,1));
    return $result;
}

/**
 * assuming theere's many temps for a day for a given sensor, get an array of the most current
 *
 * @param $id int of ID of the sensor
 * @param null $date string in YEAR-MO-DAY format, defaults to today if none passed
 * @return array of results - if no data found, array of "No Data Found" returned
 */
function getMostRecentTemp($id, $date = null)
{
    global $YANPIWS;
    if ($date == null) {
        $date = date('Y-m-d', time());
    }
    $allData = getData($YANPIWS['dataPath'] . $date);
    if (isset($allData[$id])) {
        $result = array_pop($allData[$id]);

        $label = '';
        if (isset($YANPIWS['labels'][$id])) {
            $label = $YANPIWS['labels'][$id];
        }

        $finalResult['date'] = trim($result[0]);
        $finalResult['id'] = trim($result[1]);
        $finalResult['temp'] = trim($result[2]);
        $finalResult['label'] = $label;
        $finalResult['humidity'] = trim($result[3]);
    } else {
        $finalResult['date'] = 'NA';
        $finalResult['id'] = 'No Data Found';
        $finalResult['temp'] = 'NA';
        $finalResult['label'] = 'NA';
        $finalResult['humidity'] = 'NA';
    }
    return $finalResult;
}

/**
 * given an array from getMostRecentTemp(), format it into html
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getTempHtml($tempLine)
{
    if (isset($tempLine['temp']) && $tempLine != null) {
        $temp = number_format($tempLine['temp'], 0);
        return "<span class='degrees'>{$temp}°</span>" .
            "<span class='label'>{$tempLine['label']}</span>\n";
    } else {
        return "NA\n";
    }
}
/**
 * given an array from getMostRecentTemp(), format it into html
 *
 * @param $tempLine array from getMostRecentTemp()
 * @return string of HTML
 */
function getHumidityHtml($tempLine, $useLabel = false)
{
    global $YANPIWS;
    $key = $tempLine['id'];
    if (isset($YANPIWS['labels'][$key])) {
        $label = $YANPIWS['labels'][$key];
    } else {
        $label = "#$key";
    }
    if (isset($tempLine['humidity']) && $tempLine != null) {
        $humidity = number_format(trim($tempLine['humidity']), 0);
        $result = "<span class='percent'>{$humidity}%</span>";
        if($useLabel) {
            $result .= "<span class='label'>$label</span>\n";
        }
        return $result;
    } else {
        return "NA\n";
    }
}

/**
 * Get the age in human time (sec, min, hour etc) of the dark sky cache
 * @param $returnSeconds boolean to return int of seconds if true, otherwise string of human time
 */
function getCacheAge($returnSeconds = false){
    global $YANPIWS;
    $path = $YANPIWS['dataPath'];
    $darkskytime =  filemtime($path . 'darksky.cache');
    if (!$returnSeconds) {
        return getHumanTime(time() - $darkskytime);
    } else {
        return (time() - $darkskytime);
    }
}

/**
 * given an array from getMostRecentTemp(), format it into debug html showing age of temp
 *
 * @param $tempLine array from getMostRecentTemp()
 * @param $returnOnlySeconds boolean to return int of seconds if true, otherwise string of human time
 * @return string of HTML
 */
function getTempLastHtml($tempLine, $returnOnlySeconds = false)
{
    global $YANPIWS;
    if ($tempLine['date'] == "NA") {
        $age = '';
        $label = '';
        return "<li>$label: $age ". implode(" - ", $tempLine) . "</li>";
    } else {
        $lineEpoch = strtotime($tempLine['date']);
        $age = getHumanTime(time() - $lineEpoch);
        $temp = "{$tempLine['temp']}°";
        $id = $tempLine['id'];
        $label = $tempLine['label'];
        if (!$returnOnlySeconds) {
            return "<li>$label: $temp $age ago</li>";
        } else {
            return (time() - $lineEpoch);
        }
    }
}

/**
 * given an epoch timestampe, return html with icon
 *
 * @param $time int of epoch
 * @return string of html
 */
function getSunsetHtml($time)
{
    $time = date('g:i A', $time);
    return '<img src="moon.svg" class="moon" /> '. $time;
}

/**
 * given an epoch timestampe, return html with icon
 *
 * @param $time int of epoch
 * @return string of html
 */
function getSunriseHtml($time)
{
    $time = date('g:i A', $time);
    return '<img src="sun.svg" class="sun" /> '. $time;
}

/**
 * get data from dark sky.  will cache data and refresh it every 10 minutes
 *
 * @return stdClass of either resutls or very lightly populated error object
 */
function getDarkSkyData()
{
    global $YANPIWS;
    $path = $YANPIWS['dataPath'];
    $cache = $path . 'darksky.cache';
    $hourAgo = time() - (60*10); // 10 minutes
    $data = false;
    $configStatus = configIsValid();
    if($configStatus['valid'] === true) {
        if ((!is_file($cache) || filectime($cache) < $hourAgo)) {
            $http = curl_init(getDarkSkyUrl());
            curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
            $dataFromRemote = curl_exec($http);
            $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
            if ($http_status == 200){
                file_put_contents($cache, serialize(json_decode($dataFromRemote)));
            } else {
                // do error handling/logging here
            }
        }

        // alwasy vetch from
        if (is_file($cache)) {
            $data = unserialize(file_get_contents($cache));
        }
    }
    if ($data === false || $data === null) {
        $data = new stdClass();
        $data->daily = null;
        $data->currently = null;
    }
    return $data;
}

/**
 * Simple wrapper to concat the string for the Dark Sky API endpoint
 *
 * @return string of URL
 */
function getDarkSkyUrl($useTestLatLong = false){
    global $YANPIWS;
    if ($useTestLatLong){
        $lat = "31.775554";
        $lon = "81.822436";
    } else {
        $lat = $YANPIWS['lat'];
        $lon = $YANPIWS['lon'];
    }
    return 'https://api.darksky.net/forecast/' . $YANPIWS['darksky'] . '/' . $lat . ',' . $lon;
}

/**
 * expects the $data->daily object from getDarkSkyData(), returns $days (default 5) of forecast HTML
 *
 * @param null $daily $data->daily object from getDarkSkyData()
 * @param int $days how many days of forecast to return
 * @return string of HTML
 */
function getDailyForecastHtml($daily = null, $days = 5)
{
    global $YANPIWS;
    $html = '';
    $animate = $YANPIWS['animate'];
    if ($daily == null) {
        // show rain for error
        $html .= "<img src='./skycons/rain.png' class='errorImg'  /> ";
        $html .= "No Dark Sky Data for forecast.";
    } else {
        $count = 1;
        foreach ($daily->data as $day) {
            if($count > $days) {
                break;
            }

            if ($count == 1) {
                $today = "Today";
            } else {
                $today = substr(date('D', $day->time), 0, 3);
            }
            $html .= "<div class='forecastday'>";
            $html .= "<div class='forcastDay'>$today</div>";
            if ($animate === 'true') {
                $html .= "<canvas id='$today.$day->icon' class='forecasticon' width='70' height='70'></canvas>";
            } else {
                $html .= "<img src='./skycons/{$day->icon}.png' width='70' height='70' />";
            }
            $html .= '<div class="hight spreadtemp">' . number_format($day->temperatureMax, 0) . '°</div>';
            $html .= '<div class="lowt spreadtemp">' . number_format($day->temperatureMin, 0) . '°</div>';
            $html .= '<div class="wind"> ' . number_format($day->windSpeed, 0) .  ' mph</div>';
            $html .= '</div>'. "\n";
            $count++;
        }
    }
    return $html;
}

/**
 * expects the $data->daily object from getDarkSkyData(), returns $days (default 5) of forecast HTML
 *
 * @param null $daily $data->daily object from getDarkSkyData()
 * @param int $days how many days of forecast to return
 * @return string of HTML
 */
function getDailyForecast($daily = null, $days = 5)
{
    $result = array();
    if ($daily == null) {
        $result['result'] = "No Dark Sky Data for forecast.";
    } else {

        $count = 1;
        foreach ($daily->data as $day) {
            if($count > $days) {
                break;
            }
            $dayAry = array();

            // figure which day it is
            if ($count == 1) {
                $today = "Today";
            } else {
                $today = substr(date('D', $day->time), 0, 3);
            }

            // assemble result array
            $dayAry['day'] = $today;
            $dayAry['High'] =  number_format($day->temperatureMax, 0);
            $dayAry['Low'] =  number_format($day->temperatureMin, 0);
            $dayAry['Wind'] = number_format($day->windSpeed, 0) .  ' mph';
            $dayAry['Icon'] = $day->icon;

            $result[] = $dayAry;
            $count++;
        }

    }

    return json_encode($result);
}


/**
 * given an int of seconds, return sec, min, hours or days, rounded
 * thanks http://www.kavoir.com/2010/09/php-get-human-readable-time-from-seconds.html
 *
 * @param $s int of seconds
 * @return string of human time
 */
function getHumanTime($s)
{
    $m = $s / 60;
    $h = $s / 3600;
    $d = $s / 86400;
    if ($m > 1) {
        if ($h > 1) {
            if ($d > 1) {
                return (int)$d.' days';
            } else {
                return (int)$h.' hours';
            }
        } else {
            return (int)$m.' minutes';
        }
    } else {
        return (int)$s.' seconds';
    }
}


/**
 * expects the $data->currently object from getDarkSkyData(), returns windspeed HTML
 *
 * @param null $daily $data->currently object from getDarkSkyData()
 * @return string of HTML
 */
function getCurrentWind($currentlyObject)
{
    return number_format($currentlyObject->windSpeed, 0) . " mph";
}

function getConfigValue($key){
    global $YANPIWS;
    if (in_array($key,getValidConfigs())){
        return print htmlspecialchars($YANPIWS[$key], ENT_QUOTES, 'UTF-8');
    } else {
        return 'Invalid Config Requested';
    }
}

/**
 * Thanks to https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1 for these
 * next three lat long funcions
 */
/**
 * Validates a given latitude $lat
 *
 * @param float|int|string $lat Latitude
 * @return bool `true` if $lat is valid, `false` if not
 */
function validateLatitude($lat) {
    return preg_match('/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $lat);
}
/**
 * Validates a given longitude $long
 *
 * @param float|int|string $long Longitude
 * @return bool `true` if $long is valid, `false` if not
 */
function validateLongitude($long) {
    return preg_match('/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/', $long);
}
/**
 * Validates a given fontsize $font_time_date_wind
 *
 * @param int fontSize Font Size (pt)
 * @return bool `true` if $fontSize is valid, `false` if not
 */
function validateFontSize($fontSize) {
    // thanks https://www.php.net/manual/en/function.is-int.php#82857
    return(ctype_digit(strval($fontSize)));
}
/**
 * Validates a given coordinate
 *
 * @param float|int|string $lat Latitude
 * @param float|int|string $long Longitude
 * @return bool `true` if the coordinate is valid, `false` if not
 */
function validateLatLong($lat, $long) {
    return preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $lat.','.$long);
}
