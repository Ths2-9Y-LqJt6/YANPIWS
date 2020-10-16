<?php
global $YANPIWS;
require_once 'get_data.php';
getConfig();

$forecast = getDarkSkyData();
$status = configIsValid();
$statusHtml = '';
if ($status['valid'] != true) {
    $statusHtml .= "<div class='error'>ERROR: {$status['reason']}</div>";
    $statusHtml .= "<style>.temp,.suntimes{display:none;}</style>";
}

$count = 1;
$refreshTempJS = '';
$tempsHtml = '';
foreach ($YANPIWS['labels'] as $id => $label) {
    $tempsHtml .= "\t\t\t<div class='temp temp{$count}' id='temp{$count}'></div>\n";
    $refreshTempJS .= "\t\trefeshData('temp&id={$id}',\t'temp',\t'#temp{$count}');\n";
    $count++;
    if ($count > $YANPIWS['temp_count']) {
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <script src='skycons/skycons.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script src='jquery-3.5.1.min.js?<?= $YANPIWS['cache_bust'] ?>'></script>
    <script src="./YANPIWS.js?<?php echo $YANPIWS['cache_bust'] ?>"></script>
    <script>var skycons = new Skycons({'color': 'white'});</script>
</head>
<body>
<link rel="stylesheet" type="text/css" href="styles.css.php?<?=  $YANPIWS['cache_bust'] ?>" />

<?= $statusHtml ?>

<div id="YANPIWS" class="YANPIWS"><a href="/stats.php">YANPIWS</a></div>

<div class="col">
    <div class="row temp-row">
        <a href="/temps.php">
            <?= $tempsHtml ?>
        </a>
    </div>
</div>
<div class="col">
    <div class="row"></div>
    <div class="row ">
        <div id='datetimewind'>
            <div id="wind_now" class="wind_now small_time big_clock_hide"></div>     
            <div id='datetime'>
                <div id='time' class='small_time'></div>
                <div id='date' class='small_time'></div>
            </div>
        </div>
    </div>
    <div class="row suntimes big_clock_hide">
        <span><img src="sun.svg" class="sun" /> <span id="sunrise" ></span> </span>
        <span> <img src="moon.svg" class="moon" /> <span id="sunset" ></span></span>
    </div>
</div>
<div class="col rigthtCol big_clock_hide" id="forecast">
</div>
<span id="last_ajax"></span>
<script>
    var clockState = 'small';
    $( "#datetime" ).click(function() {
        if (clockState == 'small'){
            clockState = 'big';
        } else {
            clockState = 'small';
        }
        setClockSize(clockState, <?= $YANPIWS['font_time_date_wind']?>);
    });
    function refreshAll() {
        //          Endpoint    data        DOM Location    callback
        refeshData('sunrise',   'sunrise',  '#sunrise');
        refeshData('sunset',    'sunset',   '#sunset');
        refeshData('wind_now',  'wind',     '#wind_now');
        refeshData('datetime',  'date',     '#date');
        refeshData('datetime',  'time',     '#time');
        refeshData('forecast',  'forecast', '#forecast', animateForecast);
        refeshData('age',       'age',      '#YANPIWS a');
        refeshData('last_ajax', 'last_ajax','#last_ajax');
        setClockSize(clockState, <?= $YANPIWS['font_time_date_wind']?>);
<?= $refreshTempJS ?>
    }
    refreshAll();
    setInterval ( refreshAll, 60000 );
</script>
</body>
</html>
