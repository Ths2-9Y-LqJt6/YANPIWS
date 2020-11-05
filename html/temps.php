
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="styles.css.php" />
    <script src="jquery-3.5.1.min.js" ></script>
    <script src="jquery.jqplot.min.js" ></script>
    <link rel="stylesheet" href="HappyHistogram.min.css">
    <link rel="stylesheet" href="jquery.jqplot.min.css">
    <style>
        .chart { height: 98%; width:70%; float:left;}
        .temp, .temp .label { font-size: 15pt; float: right; clear: right;}
    </style>
</head>
<body>
<?php
require_once 'get_data.php';
getConfig();
$forecast = getDarkSkyData();
$status = configIsValid();

// get two days worth of data and merge them so we can ensure we have a
// a rolling 24 hours of data
$data1 = getData($YANPIWS['dataPath'] . '/' . date('Y-m-d', time()));
$data2 = getData($YANPIWS['dataPath'] . '/' . date('Y-m-d', strtotime('yesterday')));
$data = mergeDayData($data1 ,$data2, $YANPIWS['labels']);
// todo - mergeDayData doesn't percolate up correctly to the graph to offer more
// than 
//die('<pre>eeew $data:' .print_r($data,1));
$colors = array(null,'white','red','yellow','blue');
?>
<div class="col">
    <div class="row">

        <div id='chart1' class='chart'></div>
        <?php
        $count = 1;
        foreach ($YANPIWS['labels'] as $id => $label){
            $color = $colors[$count];
            echo "<div class='temp' id='temp{$count}' style='color:$color'></div>";
            $count++;
        }
        ?>
    </div>
</div>

<script src="./YANPIWS.js"></script>
<script>
    <?php
    $count = 1;
    $tempsJsArray = '';
    $tempsJsSeries = "\n";
    foreach ($YANPIWS['labels'] as $id => $label){
        $color = $colors[$count];
        $hourlyTemps = convertDataToHourly($data[$id]);
        $tempsJsArray .= "\n[";
        foreach ($hourlyTemps as $hour => $temp) {
            $tempsJsArray .= "[$hour,$temp],";
        }
        $tempsJsArray .= '],';

        $tempsJsSeries .= "{color: '$color',markerOptions:{size:0} }";
        if (sizeof($YANPIWS['labels']) > $count){
            $tempsJsSeries .= ",\n";
        } else {
            $tempsJsSeries .= "\n";
        }
        echo "\trefreshTemp($id,$count);\n";
        $count++;
    }
    ?>

    temp = {
        grid: {
            backgroundColor: "black",
            gridLineColor: 'grey',
            gridLineWidth: false
        },

    };
    var plot1 = $.jqplot ('chart1', [<?php print $tempsJsArray?> ],
        {
            axes: {
                xaxis: {
                  pad: .5
                }
              },
            seriesDefaults: {
                rendererOptions: {
                    smooth: true
                },
            },
            series:[<?php print $tempsJsSeries?>]

      },
    );
    temp = plot1.themeEngine.newTheme('temp', temp);
    plot1.activateTheme('temp');

    /**
     * AJAX call to get updated temps
     *
     * @param id int of sensor ID
     * @param id2 string of the DOM ID to put the results in - will concat "temp" + id2
     */
    function refreshTemp(id, id2){
        loadXMLDoc('./ajax.php?content=temp&cooked=yes_sir&id=' + id, 'temp' + id2);
    }
</script>

</body>
</html>
