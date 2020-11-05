<?php
global $YANPIWS;
require_once 'get_data.php';
getConfig();
configIsValid();
$tempWidth = round(100/$YANPIWS['temp_count']);
header("Content-type: text/css");
?>

body {
    margin: 10px;
    padding: 10px;
    background-color: black;
    color: white;
    font-size: 22pt;
    font-family: sans-serif;
}
a {
    color: white;
}
.error {
    color: red;
    font-size: 30pt;
}
.errorImg {
    width:60px;
}
.homeLink {
    font-size:29pt;
}
.YANPIWS a {
    color: darkgreen;
    text-decoration: none;
    font-size:12pt;
}
a.yellow {
    color: yellow;
}
.YANPIWS {
    position: fixed;
    z-index: 10000;
    left: 42%;
    top: -10px;
}
.forecastday {
    width:20%;
    float:left;
    text-align: center;
}
.temp {
    padding-bottom:10px;
    font-size: <?= $YANPIWS['font_temp']?>pt;
    float: left;
    text-align: center;
    width: <?= $tempWidth ?>%;
}

.temp:last-child  {
    text-align: right;
}
.temp:first-child  {
    text-align: left;
}

.degrees {
    font-weight: bold;
}
.col {
    float:left;
    width: 100%;
}
.rigthtCol{
    padding-top:20px;
}
.row {
    padding-bottom: 5px;
    clear:both;
    width: 100%;
}
.sun {
    width:33px;
}
.moon {
    width:25px;
    padding-left:20px;
}
#date, #time, .label, .wind_now {
    font-size:<?= $YANPIWS['font_time_date_wind']?>pt;
    font-weight: bold;
}

.label {
    text-transform: uppercase;
    font-size: <?= $YANPIWS['font_temp_label']?>pt;
}
#date, #time{
    float: left;
}
#time:hover{
    cursor: pointer;
}
.wind_now {
    float: right;
}
#date {
    padding-left: 20px;
}
.lowt {
    color: #476b6b;
}
.spreadtemp {
    font-size:18pt;
}
.wind {
    font-size:12pt;
}
.suntimes {
    padding-top: 20px;
}
label {
    clear: both;
    float: left;
    width: 25%;
    text-align: left;
}
input {
    width:70%;
}
#last_ajax {
    display: none;
}
@media only screen and (max-width : 480px) {
    body {
        font-size: 15pt;
    }
    .error {
        color: red;
        font-size: 20pt;
    }
    .errorImg {
        width:40px;
    }
    .temp {
        font-size: 25pt;
    }
    .rigthtCol{
        padding-top:10px;
    }
    .date, .time, .label, .wind_now {
        font-size:15pt;
    }
    .spreadtemp {
        font-size:11pt;
    }
    .YANPIWS a{
        font-size:15pt;
    }
    .suntimes {
        padding-top: 10px;
    }
    canvas {
        width:50px;
        height:50px;
    }
}
