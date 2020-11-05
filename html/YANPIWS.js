/**
 * do a GET and stuff results into an ID, optinally call call back
 * thanks http://stackoverflow.com/a/8567149 - look ma - no jquery!
 *
 * @param URL string URL to fetch contnet from
 * @param targetId string DOM ID where to innerHTML the result
 * @param callback function to callback when done, optional
 */
function loadXMLDoc(URL, targetId, callback) {
    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE ) {
            if (xmlhttp.status == 200) {
                document.getElementById(targetId).innerHTML = xmlhttp.responseText;

                if (typeof callback === "function") {
                    callback();
                }
            } else {
                document.getElementById(targetId).innerHTML = "AJAX Failed :(";
            }
        }
    };

    xmlhttp.open("GET", URL, true);
    xmlhttp.send();

}

/**
 * handle resizing clock to big then small
 */
// todo - use class for defaultSize, remove from signature
function setClockSize(state, defaultSize){
    if (state == 'big'){
        // todo - put all this in a class and then just add and remove class
        $('#time').css('font-size', '127pt').css('text-align', 'center').css('width', '100%');
        $('#date').css('text-align', 'center').css('width', '100%');
        $('.big_clock_hide').hide();
    } else {
        // todo - put all this in a class and then just add and remove class
        $('#time').css('font-size', defaultSize + 'pt').css('text-align', 'left').css('width', 'inherit');
        $('#date').css('text-align', 'left').css('width', 'inherit');
        $('.big_clock_hide').show();
        console.log('using small size: ' + defaultSize);
    }
}

/**
 * AJAX call to get updated forecast
 */
function refreshForecast(){
    loadXMLDoc('./ajax.php?content=forecast', 'forecast', animateForecast);
}

/**
 * AJAX call to get updated sunset time
 */
function refreshSunset(){
    loadXMLDoc('./ajax.php?content=sunset', 'sunset');
}

/**
 * AJAX call to get updated sunrise time
 */
function refreshSunrise(){
    loadXMLDoc('./ajax.php?content=sunrise', 'sunrise');
}

/**
 * AJAX call to get updated content and return JSON
 */
function refeshData(endpoint, dataElement, target, callback = false){
    let baseUrl = './ajax.php?content=';
    $.getJSON( baseUrl + endpoint, function( data ) {
        $(target).html(data[dataElement]);
        if (typeof callback === "function") {
            callback();
        }
    });
}
/**
 * start the dark sky canvas DOM elements animating. intended to call if
 * canvas elements have been updated from refreshForecast()
 */
function animateForecast() {
    var elements = document.querySelectorAll('.forecasticon');
    var canvasArray;
    Array.prototype.forEach.call(
        elements, function(el, i){
            canvasArray = el.getAttribute('id').split('.');
            skycons.add(el.getAttribute('id'), canvasArray[1]);
        }
    );
    skycons.play();
}
