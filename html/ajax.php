<?php
require_once ("get_data.php");
getConfig();
if (isset($_GET['content'])){

    $today = date('Y-m-d', time());
    $time = date('g:i A', time());
    $date = date('D M j', time());
    $forecast = getDarkSkyData();

    switch ($_GET['content']){
        case "forecast":
            if (isset($forecast->daily)) {
                print json_encode(array('forecast' => getDailyForecastHtml($forecast->daily)));
            }
            break;
        case "forecast_full_json":
            if (isset($forecast->daily)) {
                print json_encode($forecast->daily->data);
            }
            break;

        case "wind_now":;
            if (isset($forecast->currently)) {
                print json_encode(array('wind' => getCurrentWind($forecast->currently)));
            }
            break;

        case "sunset":
            if (isset($forecast->daily->data[0]->sunsetTime)){
                $time = date('g:i A', $forecast->daily->data[0]->sunsetTime);
                print json_encode(array('sunset' => $time));
            }
            break;

        case "sunrise":
            if (isset($forecast->daily->data[0]->sunriseTime)){
                $time = date('g:i A', $forecast->daily->data[0]->sunriseTime);
                print json_encode(array('sunrise' => $time));
            }
            break;

        case "age":
            $cacheAge = getCacheAge(true);
            $maxTempAge = 0;
            foreach ($YANPIWS['labels'] as $id => $label){
                $tempLine = getMostRecentTemp($id);
                $currentTempAge = getTempLastHtml($tempLine, true);
                if ($maxTempAge < $currentTempAge){
                    $maxTempAge = $currentTempAge;
                }
            }
            if ($currentTempAge > 600 || $maxTempAge > 600){
                // todo - refactor calls to not expect cooked HTML in respone, just raw JSON
                $result['age'] = '<span style="color: yellow">YANPIWS</span>';
            } else {
                $result['age'] = 'YANPIWS';
            }
            print json_encode($result);
            break;

        case "datetime":
            print json_encode(array('date' => $date, 'time' => $time));
            break;

        case "temp":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                if(isset($_GET['cooked'])){
                    print getTempHtml($tempLine);
                } else {
                    // todo - refactor calls to not expect cooked HTML in respone, just raw JSON
                    print json_encode(array('temp' => getTempHtml($tempLine)));
                }
            }
            break;

        case "humidity":
            if (isset($_GET['id']) && isset($YANPIWS['labels'][$_GET['id']])){
                $tempLine = getMostRecentTemp($_GET['id']);
                print json_encode(array($tempLine));
            }
            break;

        case "last_ajax":
            // update this ajax file per #61 https://github.com/Ths2-9Y-LqJt6/YANPIWS/issues/61
            touch($YANPIWS['dataPath'] . '/' . 'last_ajax');
            print json_encode(array('last_ajax' => time()));
            break;
    }
}
exit;
