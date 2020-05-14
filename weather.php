<?php

/**
 * This program is Weather forecast web application for coding assignment.
 * You can redistribute it and/or modify except api keys.
 *
 * Used following API:  
 * 
 *      Google Maps Platform -> Web services -> Geocoding API
 *      https://developers.google.com/maps/documentation/geocoding/intro
 *      Provide Location name in English
 * 
 *      Google Maps Platform -> Web -> Maps Static API
 *      https://developers.google.com/maps/documentation/maps-static/dev-guide
 *
 *      Google Maps Platform -> Web -> Maps Embed API
 *      https://developers.google.com/maps/documentation/embed/guide
 *
 *      Yahoo! Geocoder API 
 *      https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/geocoder.html
 *      Provide Location name in Japanese
 * 
 *      Open Weather -> Weather API
 *      https://openweathermap.org/forecast5
 *      Provide 5 days forecast by every 3 hours record
 * 
 *      Weatherbit.io -> Weather API
 *      https://www.weatherbit.io/api/weather-forecast-16-day
 *      Provide 16 days forecast by daily
 * 
 *      Japan COVID-19 Coronavirus Tracker - https://covid19japan.com/
 *      https://github.com/reustle/covid19japan-data/
 * 
 * Author: Enkhbaatar
 *
 * @file weather.php
 */

// global variable to be used to store and display data
$area = null;

// api keys
$apikey_googlemap = "your_api_key";
$apikey_yahooapis = "your_api_key";
$apikey_openweathermap = "your_api_key";
$apikey_weatherbit = "your_api_key";

/**
 * Class to get and store data for APIs
 */
class Area
{
    private $postcode = '';   // postcode to search a location
    private $location = '';   // lat, long coordinates 
    private $name = '';       // location name (prefecture, city, district)
    private $maptype = 'Static MAP';  // MAP can be used as static and dynamic
    private $unit = 'CELSIUS';  // temperature measure units. CELSIUS or FARENHEIT 
    private $apiLocation = 'Google';  // Geocoding API providers. Google or Yahoo.
    private $apiWeather = 'OpenWeatherMap'; // Weather API providers. OpenWeatherMap or WeatherBit
    public $forecast = [];   // weather forecast data
    public $errorMsg = '';   // error messages to display
    public $prefecture = '';  // prefecture name used to get Covid19 data

    /**
    * send GET request to API providers using curl
    *
    * @return string 
    */
    private function callAPI($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
    * display MAP depends on selection
    *
    * @return boolean 
    */
    public function showMap()
    {
        return $this->maptype === 'Embed MAP' ? $this->showEmbedMap() : $this->showStaticMap();
    }

    /**
    * set customized selection for map, unit and api providers
    *
    * @return void 
    */ 
    public function setConfig()
    {
        $this->postcode = isset($_POST['postcode']) ? htmlspecialchars($_POST['postcode']) : '000-0000';
        $this->maptype = isset($_POST['maptype']) ? htmlspecialchars($_POST['maptype']) : 'Static MAP';
        $this->unit = isset($_POST['unit']) ? htmlspecialchars($_POST['unit']) : 'CELSIUS';
        $this->apiLocation = isset($_POST['apiLocation']) ? htmlspecialchars($_POST['apiLocation']) : 'Google';
        $this->apiWeather = isset($_POST['apiWeather']) ? htmlspecialchars($_POST['apiWeather']) : 'OpenWeatherMap';
    }

    /**
    * call selected api to find a location
    *
    * @return boolean 
    */ 
    public function getLocation()
    {
        return $this->apiLocation === 'Yahoo' ? $this->getLocationYahoo() : $this->getLocationGoogle();
    }

    /**
    * call selected api to get a weather forecast
    *
    * @return boolean 
    */       
    public function getWeather()
    {
        return $this->apiWeather === 'WeatherBit.io' ? $this->getWeatherBit() : $this->getOpenWeatherMap();
    }

    /**
    * call google geocoding API to get coordinates of location.
    * 
    * @return boolean 
    */
    private function getLocationGoogle()
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json';   // api url
        $url .= '?address=' . $this->postcode;    // postcode to search
        $url .= '&components=country:JP';   // filter by country Japan to prevent from ambiguos results
        $url .= '&key=' . $GLOBALS['apikey_googlemap'];  // api key
        $result = $this->callAPI($url);
        // call function to extract api response 
        return $result ? $this->extractGoogleResult(json_decode($result)) : false;
    }

    /**
    * call Yahoo geocoding API to get coordinates of location. 
    *
    * @return boolean 
    */
    private function getLocationYahoo()
    {
        $url = 'https://map.yahooapis.jp/search/zip/V1/zipCodeSearch';  // api url
        $url .= '?query=' . $this->postcode;   // postcode to search
        $url .= '&output=json';    // output format. Can be json or xml. Used json format.  
        $url .= '&detail=full';    // to get complete location name
        $url .= '&zkind=0';   // zipcode type. 0 = town
        $url .= '&appid=' . $GLOBALS['apikey_yahooapis'];
        $result = $this->callAPI($url);
        // call function to extract api response
        return $result ? $this->extractYahooResult(json_decode($result)) : false;
    }

    /**
    * call WeatherBit weather API to get forecast data.   
    * API response will be daily forecast data.
    *
    * @return boolean 
    */
    private function getWeatherBit()
    {
        $url = 'https://api.weatherbit.io/v2.0/forecast/daily';
        $url .= '?lat=' . $this->location->lat;
        $url .= '&lon=' . $this->location->lng;
        $url .= '&units=' . ($this->unit === "FAHRENHEIT" ? "I" : "M");
        $url .= '&days=3';   // default return 16 days forecast. Using forecast of 3 days.
        $url .= '&key=' . $GLOBALS['apikey_weatherbit'];
        $result = $this->callAPI($url);
        return $result ? $this->extractWeatherBitResult(json_decode($result)) : false;
    }

    /**
    * call OpenWeatherMap weather API to get forecast data.   
    * API response will be 3 hourly forecast data.
    *
    * @return boolean 
    */
    private function getOpenWeatherMap()
    {
        $url = 'https://api.openweathermap.org/data/2.5/forecast';
        $url .= '?lat=' . $this->location->lat;
        $url .= '&lon=' . $this->location->lng;
        $url .= '&units=' . ($this->unit === "FAHRENHEIT" ? "imperial" : "metric");
        $url .= '&appid=' . $GLOBALS['apikey_openweathermap'];
        $result = $this->callAPI($url);
        return $result ? $this->extractOpenWeatherMapResult(json_decode($result)) : false;
    }

    
    /**
    * extract API response from WeatherBit
    * @param string $json This parameter should contain json string of API response
    *
    * @return boolean 
    */
    private function extractWeatherBitResult($json)
    {
        // check main data field
        if (!isset($json->data)) {
            $this->errorMsg = "'data' field not found in the response object!";
            return false;
        }
        // check main data field has records 
        if (count($json->data) === 0) {
            $this->errorMsg = "Weather forecast data not found in the response object!";
            return false;
        }
           
        // display units will be different depends on selection
        $degree_simbol = $this->unit === 'FAHRENHEIT' ? "<span>&#8457;&nbsp;</span>" : "<span>&#8451;&nbsp;</span>";
        $wind_unit = $this->unit === 'FAHRENHEIT' ? " m/h" : " m/s";

        // weatherBit provide daily forecast data
        foreach ($json->data as $day_data) {
            $weather = (object)[];
            $weather->dt = $day_data->valid_date; // date
            $weather->wind = number_format(floatval($day_data->wind_spd), 2) . $wind_unit;
            $weather->humidity = $day_data->rh . "%";
            $weather->temp = $day_data->temp . $degree_simbol;
            $weather->temp_min = round($day_data->min_temp) . $degree_simbol;
            $weather->temp_max = round($day_data->max_temp) . $degree_simbol;
            $weather->description = $day_data->weather->description;
            // make icon image url using icon code
            $weather->icon = "https://www.weatherbit.io/static/img/icons/" . $day_data->weather->icon . ".png";
            array_push($this->forecast, $weather);
        }        
        return true;
    }

    /**
    * extract API response from OpenWeatherMap
    * @param string $json This parameter should contain json string of API response
    *
    * @return boolean 
    */
    private function extractOpenWeatherMapResult($json)
    {
        // check response status field
        if (!isset($json->cod)) {
            $this->errorMsg = "'cod' field not found in the response object!";
            return false;
        }
         
        // check response status field has success code
        if ($json->cod !== '200') {
            $this->errorMsg = "API error! status code=" . $json->cod;
            return false;
        }

        // check main data field
        if (!isset($json->list) || count($json->list) === 0) {
            $this->errorMsg = "Weather forecast data not found in the response object!";
            return false;
        }
          
        // display units will be different depends on selection
        $degree_simbol =  $this->unit === 'FAHRENHEIT' ? "<span>&#8457;&nbsp;</span>" : "<span>&#8451;&nbsp;</span>";
        $wind_unit = $this->unit === 'FAHRENHEIT' ? " m/h" : " m/s";
        
        // OpenWeatherMap provide forecast data for every 3 hours.
        // calculating daily min, max temperature
        $prev = '';
        $count = 0;
        foreach ($json->list as $hour_data) {
            // convert timestamp in UTC to Japan time and extract day part
            $dt = gmdate("Y-m-d", intval($hour_data->dt)+9*60*60);
            if ($dt === $prev) {
                // find dialy max values
                $weather->wind = max($weather->wind, floatval($hour_data->wind->speed));
                $weather->humidity = max($weather->humidity, floatval($hour_data->main->humidity));
                $weather->temp = max($weather->temp, intval($hour_data->main->temp));
                $weather->temp_min = min($weather->temp_min, intval($hour_data->main->temp_min));
                if ($weather->temp_max < intval($hour_data->main->temp_max)) {
                    $weather->temp_max = intval($hour_data->main->temp_max);
                    // get icon and description for max temp
                    if (count($hour_data->weather) > 0) {
                        $weather->description = $hour_data->weather[0]->main;
                        $weather->icon = "https://openweathermap.org/img/wn/" . $hour_data->weather[0]->icon . "@2x.png";
                    }
                }
            } else {
                if ($prev !== '') {
                    // format and add object to the Area, if there are previos records
                    $weather->wind = number_format(floatval($weather->wind), 2) . $wind_unit;
                    $weather->humidity = $weather->humidity . "%";
                    $weather->temp = $weather->temp . $degree_simbol;
                    $weather->temp_min = $weather->temp_min . $degree_simbol;
                    $weather->temp_max = $weather->temp_max . $degree_simbol;
                    array_push($this->forecast, $weather);
                    // 3 days forecast required
                    if (++$count>2) {
                        break;
                    }
                }
               
                // next day start
                $weather = (object)[];
                $weather->dt = $dt;
                $prev = $dt;
                $weather->wind = floatval($hour_data->wind->speed);
                $weather->humidity = floatval($hour_data->main->humidity);
                $weather->temp = intval($hour_data->main->temp);
                $weather->temp_min = intval($hour_data->main->temp_min);
                $weather->temp_max = intval($hour_data->main->temp_max);
                if (count($hour_data->weather) > 0) {
                    $weather->description = $hour_data->weather[0]->main;
                    $weather->icon = "https://openweathermap.org/img/wn/" . $hour_data->weather[0]->icon . "@2x.png";
                }
            }
        }
        // if days are less than 3, add last object
        if ($count<2) {
            $weather->wind = number_format(floatval($weather->wind), 2) . $wind_unit;
            $weather->humidity = $weather->humidity . "%";
            $weather->temp = $weather->temp . $degree_simbol;
            $weather->temp_min = $weather->temp_min . $degree_simbol;
            $weather->temp_max = $weather->temp_max . $degree_simbol;
            array_push($this->forecast, $weather);
        }
        return true;
    }

    /**
    * extract API response from Google geocoding
    * @param string $json This parameter should contain json string of API response
    *
    * @return boolean 
    */
    private function extractGoogleResult($json)
    {
        // check status field has received
        if (!isset($json->status)) {
            $this->errorMsg = "'status' field not found in the response object!";
            return false;
        }
        // check if no records found
        if ($json->status === 'ZERO_RESULTS') {
            $this->errorMsg = "Location not found!";
            return false;
        }
        // check if status not success
        if ($json->status !== 'OK') {
            // When the API returns an other status codes,
            // there may be an additional error_message field within the response object.
            // Used only STATUS to display error.
            $this->errorMsg = "API error! status=" . $json->status;
            return false;
        }
        // collect prefecture, city, district addresses from address_components field     
        if (count($json->results) > 0) {
            if (isset($json->results[0]->address_components)) {
                $comma = '';
                foreach ($json->results[0]->address_components as $address) {
                    // skip if address component is country or postal code
                    if (!in_array("country", $address->types) && !in_array("postal_code", $address->types)) {
                        $this->name = $address->long_name . $comma . $this->name;
                        $comma = ', ';
                        // extract prefecture name
                        if (in_array("administrative_area_level_1", $address->types)) {
                            $this->prefecture = $address->long_name;
                        }
                    }
                }
            }
            // extract coordinates
            if (isset($json->results[0]->geometry) && isset($json->results[0]->geometry->location)) {
                $this->location = $json->results[0]->geometry->location;
            }
        }
        // diplay Unknown area if location name not found
        if ($this->name === '') {
            $this->name = 'Unknown area';
        }       
        return true;
    }
    
    /**
    * extract API response from Yahoo geocoding
    * @param string $json This parameter should contain json string of API response
    *
    * @return boolean 
    */
    private function extractYahooResult($json)
    {
        // check result status field has received
        if (!isset($json->ResultInfo)) {
            $this->errorMsg = "'ResultInfo' field not found in the response object!";
            return false;
        }
        // check status
        if ($json->ResultInfo->Status !== 200) {
            $this->errorMsg = "API error! Status=" .$json->ResultInfo->Status;
            return false;
        }
        // check record count
        if ($json->ResultInfo->Count === 0) {
            $this->errorMsg = "Location not found";
            return false;
        }
        // check main data field has record  
        if (count($json->Feature) > 0) {
            // get location name
            if (isset($json->Feature[0]->Property->Address)) {
                $this->name = $json->Feature[0]->Property->Address;
            } else {
                $this->name = 'Unknown area';
            }
            // extract coordinates
            if (isset($json->Feature[0]->Geometry) && ($json->Feature[0]->Geometry->Type === 'point')) {
                $points = explode(",", $json->Feature[0]->Geometry->Coordinates);

                if (count($points) === 2) {
                    $obj = (object)[];
                    $obj->lat = $points[1];
                    $obj->lng = $points[0];
                    $this->location = $obj;
                }
            }
            // extract Prefecture name
            foreach ($json->Feature[0]->Property->AddressElement as $address) {
                if ($address->Level === 'prefecture') {
                    $this->prefecture = $address->Name;
                    break;
                }
            }
        }
        return true;
    }

    /**
    * call url to get Covid19 latest data
    *
    * @return boolean 
    */ 
    public function getCovid19data()
    {
        // if prefecture is not found from location, don't process covid19 data
        if ($this->prefecture ==='') {
            return false;
        }
        // url to get covid19 json data
        $url = 'https://data.covid19japan.com/summary/latest.json';
        $result = $this->callAPI($url);
        return $result ? $this->extractCovid19Result(json_decode($result)) : false;
    }

    /**
    * extract covid19 data
    *
    * @return void 
    */ 
    private function extractCovid19Result($json)
    {
        // check main data field "prefectures"
        if (!isset($json->prefectures)) {
            $this->errorMsg = " 'prefectures' field not found in Covid19 data response!";
            return false;
        }

        $covid = (object)[];
        if (isset($json->updated)) {
            $covid->updated = $json->updated;
        }

        foreach ($json->prefectures as $prefecture) {
            if ($prefecture->name === $this->prefecture || $prefecture->name_ja === $this->prefecture) {
                $covid->confirmed = $prefecture->confirmed;
                $covid->newlyConfirmed = $prefecture->newlyConfirmed;
                $covid->recovered = $prefecture->recovered;
                $covid->critical = $prefecture->critical;
                $covid->deaths = $prefecture->deaths;
                $this->covid19 = $covid;
            }
        }
    }

    /**
    * display static (image) map using Google Map API
    *
    * @return void 
    */
    private function showStaticMap()
    {
        if ($this->location !== '') {
            $mapurl = "https://maps.googleapis.com/maps/api/staticmap";
            $mapurl .= "?center=" . $this->location->lat . "," . $this->location->lng;
            $mapurl .= "&size=450x400";  // image size
            $mapurl .= "&zoom=15";      // zoom to town level
            // put marker center of the location
            $mapurl .= "&markers=|" . $this->location->lat . "," . $this->location->lng;  
            $mapurl .= "&key=" . $GLOBALS['apikey_googlemap'];
            return "<img src=" . $mapurl . " >";
        } else {
            return "";
        }
    }

    /**
    * display dynamic (embed) map using Google Map API
    *
    * @return void 
    */
    private function showEmbedMap()
    {
        if ($this->location !== '') {
            $mapurl = "https://www.google.com/maps/embed/v1/view";
            $mapurl .= "?center=" . $this->location->lat . "," . $this->location->lng;
            $mapurl .= "&zoom=15"; // zoom to town level
            $mapurl .= "&key=" . $GLOBALS['apikey_googlemap'];;
            
            // iframe params to embed on the web page
            $map = '<iframe ';
            $map .= 'width="450" ';
            $map .= 'height="400" ';
            $map .= 'frameborder="0" style="border:0" ';
            $map .= 'src="' .$mapurl. '" allowfullscreen> ';
            $map .= '</iframe>';
            return $map;
        } else {
            return "";
        }
    }

    /**
    * get location name 
    *
    * @return string 
    */
    public function getName()
    {
        return $this->name;
    }
}

/**
* Execution starts here
* 
*/

// check submit request or not
if (isset($_POST['Submit']) && isset($_POST['postcode'])) {
    // global variable used to display location data
    $area = new Area();
    // process POST parameters such as postcode, api providers etc.
    $area->setConfig();
    // find a location using postcode  
    if ($area->getLocation()) {
          // get weater forecast data
          if ($area->getWeather()) {
              // get Covid19 latest data
              $area->getCovid19data();
          }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
  <title>Weather app for assisment test</title>
  <style type="text/css">
    input:required:invalid, input:focus:invalid {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAeVJREFUeNqkU01oE1EQ/mazSTdRmqSxLVSJVKU9RYoHD8WfHr16kh5EFA8eSy6hXrwUPBSKZ6E9V1CU4tGf0DZWDEQrGkhprRDbCvlpavan3ezu+LLSUnADLZnHwHvzmJlvvpkhZkY7IqFNaTuAfPhhP/8Uo87SGSaDsP27hgYM/lUpy6lHdqsAtM+BPfvqKp3ufYKwcgmWCug6oKmrrG3PoaqngWjdd/922hOBs5C/jJA6x7AiUt8VYVUAVQXXShfIqCYRMZO8/N1N+B8H1sOUwivpSUSVCJ2MAjtVwBAIdv+AQkHQqbOgc+fBvorjyQENDcch16/BtkQdAlC4E6jrYHGgGU18Io3gmhzJuwub6/fQJYNi/YBpCifhbDaAPXFvCBVxXbvfbNGFeN8DkjogWAd8DljV3KRutcEAeHMN/HXZ4p9bhncJHCyhNx52R0Kv/XNuQvYBnM+CP7xddXL5KaJw0TMAF8qjnMvegeK/SLHubhpKDKIrJDlvXoMX3y9xcSMZyBQ+tpyk5hzsa2Ns7LGdfWdbL6fZvHn92d7dgROH/730YBLtiZmEdGPkFnhX4kxmjVe2xgPfCtrRd6GHRtEh9zsL8xVe+pwSzj+OtwvletZZ/wLeKD71L+ZeHHWZ/gowABkp7AwwnEjFAAAAAElFTkSuQmCC);
        background-position: right top;
        background-repeat: no-repeat;
        -moz-box-shadow: none;
    }
    input:required:valid {
        background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAepJREFUeNrEk79PFEEUx9/uDDd7v/AAQQnEQokmJCRGwc7/QeM/YGVxsZJQYI/EhCChICYmUJigNBSGzobQaI5SaYRw6imne0d2D/bYmZ3dGd+YQKEHYiyc5GUyb3Y+77vfeWNpreFfhvXfAWAAJtbKi7dff1rWK9vPHx3mThP2Iaipk5EzTg8Qmru38H7izmkFHAF4WH1R52654PR0Oamzj2dKxYt/Bbg1OPZuY3d9aU82VGem/5LtnJscLxWzfzRxaWNqWJP0XUadIbSzu5DuvUJpzq7sfYBKsP1GJeLB+PWpt8cCXm4+2+zLXx4guKiLXWA2Nc5ChOuacMEPv20FkT+dIawyenVi5VcAbcigWzXLeNiDRCdwId0LFm5IUMBIBgrp8wOEsFlfeCGm23/zoBZWn9a4C314A1nCoM1OAVccuGyCkPs/P+pIdVIOkG9pIh6YlyqCrwhRKD3GygK9PUBImIQQxRi4b2O+JcCLg8+e8NZiLVEygwCrWpYF0jQJziYU/ho2TUuCPTn8hHcQNuZy1/94sAMOzQHDeqaij7Cd8Dt8CatGhX3iWxgtFW/m29pnUjR7TSQcRCIAVW1FSr6KAVYdi+5Pj8yunviYHq7f72po3Y9dbi7CxzDO1+duzCXH9cEPAQYAhJELY/AqBtwAAAAASUVORK5CYII=);
        background-position: right top;
        background-repeat: no-repeat;
    }

    input[type=text],select {
    width:200px;
    padding:10px;
    border:1px solid #ccc;
    border-radius:4px;
    box-sizing:border-box;
    margin-top: 1px;
    margin-bottom:1px;
    }


    input[type=submit] {
    background-color:#4CAF50;
    color:#fff;
    padding:10px 20px;
    border:none;
    border-radius:4px;
    cursor:pointer;
    }

    label {
    padding:12px 20px;
    }

    h2 {
    padding:12px 20px;
    margin: 0 0 0 0;
    }

    input[type=submit]:hover {
    background-color:#45a049;
    }

    .map {
    border-radius:5px;
    background-color:#ccc;
    padding:5px;
    width:450px;
    height:400px;
    }

    .extra {
    border-radius:5px;
    background-color:#A6FFE8;
    padding:5px;
    width:400px;
    height:400px;
    display:grid;
    grid-template-columns:  40% 60%;
    grid-template-rows: 1fr 1fr 1fr 1fr 2fr 1fr 1fr 1fr;
    justify-content: center;
    align-items: center;
    margin:0;
    }

    .weather {
    border-radius:5px;
    background-color:#87ceeb;
    padding:20px;
    margin:0;
    display:grid;
    grid-template-columns:50% 50%;
    grid-template-rows:1fr 1fr 2fr 2fr 1fr 1fr;
    justify-content:center;
    align-items:center;
    }

    .date {
    grid-column:1 / span 2;
    display:flex;
    align-items:center;
    justify-content:center;
    }

    .desc {
    grid-column:1 / span 2;
    display:flex;
    align-items:left;
    justify-content:left;
    }

    .degree {
    display:flex;
    align-items:center;
    justify-content:left;
    }

    .icon {
    grid-row:3 / span 2;
    justify-content:center;
    align-items:center;
    display:flex;
    }

    .icon img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    }

    .weather h2,h3,h4,h5,h6,p {
    margin-top:0;
    margin-bottom:0;
    }

    body {
    margin:0 auto;
    max-width:56em;
    padding:1em 0;
    font-family:"Noto Sans JP";
    }

    .grid1 {
    display:grid;
    grid-template-columns:100%;
    grid-gap:1.5em;
    }

    .grid2 {
    display:grid;
    grid-template-columns:50% 50%;
    grid-gap:1.5em;
    }

    .grid4 {
    display:grid;
    grid-template-columns:25% 25% 25% 25%;
    grid-gap:1.5em;
    }

    .gridtitle {
        grid-column:1 / span 2;
        display:flex;
        align-items: center;
        justify-content: center;
    }
    .gridupdated {
        grid-column:1 / span 2;
        display:flex;
        align-items: left;
        justify-content: left;
        padding: 15px;
    }

    .griddata {
    padding: 15px;
    display:flex;
    align-items:center;
    justify-content:left;
    }

  </style>
</head>

<body>

<div>
  <form method="POST" action=<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?> >
    <div>
        <label for="postcode">Post code:</label>
        <input type="text" id="postcode" name="postcode" placeholder="000-0000"
            required maxlength=8 pattern="[0-9]{3}-[0-9]{4}"
            value="<?php if (isset($_POST['postcode'])) {
                        echo htmlspecialchars($_POST['postcode']);
                    } ?>"
        >
        <input type="submit" name="Submit" value="Submit">
    </div>
    <div class="grid2">
        <div>
        <?php
          if (isset($area)) {
              echo "<h2>" . $area->getName() ."</h2>";
          }
        ?>
        </div>
        <div>
            <?php
                if (isset($area) && $area->errorMsg) {
                    echo "<p style=\"color: red;\">*",htmlspecialchars($area->errorMsg),"</p>";
                }
            ?>
        </div>
    </div>
    <div class="grid4">   
            <?php
                // to display weather forecast data 
                if (isset($area) && isset($area->forecast)) {
                    $i = 0;
                    foreach ($area->forecast as $weather) {
                        echo '<div class="weather">';
                        echo '<div class="date"><h3>'. $weather->dt .'</h3></div>';
                        echo '<div class="date"><p>'. $weather->description .'</p></div>';
                        echo '<div class="icon"><img src='. $weather->icon .' ></div>';
                        echo '<div class="degree"><h3 style="color:red">'. $weather->temp_max .'</h3><p>high</p></div>';
                        echo '<div class="degree"><h3 style="color:blue">'. $weather->temp_min .'</h3><p>low</p></div>';
                        echo '<div class="desc"><h5>wind: '. $weather->wind .'</h5></div>';
                        echo '<div class="desc"><h5>humidity: '. $weather->humidity .'</h5></div>';
                        echo '</div>';
                        // skip if received data more than 3 days
                        if (++$i>2) {
                            break;
                        }
                    }
                }
            ?>
    </div>
    <br>
    <div class="grid2">
        <div class="map">
        <?php
            // display map
            if (isset($area)) {
                echo $area->showMap();
            }
        ?>
        </div>
        <div class="extra">
            <div><label for="maptype">MAP type</label></div>
            <div>
            <select id="maptype" name="maptype">
            
                <option value="Static MAP" 
                    <?php if (!isset($_POST['maptype']) || $_POST['maptype']!=='Embed MAP') {
            echo 'selected="selected"';
        } ?> 
                >
                    Static MAP
                </option>
                <option value="Embed MAP"
                    <?php if (isset($_POST['maptype']) && $_POST['maptype']==='Embed MAP') {
            echo 'selected="selected"';
        } ?>
                >
                    Embed MAP
                </option>
            </select>
            </div>
            <div><label for="apiLocation">Location API</label></div>
            <div>
            <select id="apiLocation" name="apiLocation">
                <option value="Google"
                <?php if (!isset($_POST['apiLocation']) || $_POST['apiLocation']!=='Yahoo') {
            echo 'selected="selected"';
        } ?> 
                >
                    Google
                </option>
                <option value="Yahoo"
                <?php if (isset($_POST['apiLocation']) && $_POST['apiLocation']==='Yahoo') {
            echo 'selected="selected"';
        } ?>
                >
                    Yahoo
                </option>
            </select>
            </div>
        
            <div><label for="apiWeather">Weather API</label></div>
            <div>
            <select id="apiWeather" name="apiWeather">
                <option value="OpenWeatherMap"
                <?php if (!isset($_POST['apiWeather']) || $_POST['apiWeather']!=='WeatherBit.io') {
            echo 'selected="selected"';
        } ?>  
                >
                    OpenWeatherMap
                </option>
                <option value="WeatherBit.io"
                <?php if (isset($_POST['apiWeather']) && $_POST['apiWeather']==='WeatherBit.io') {
            echo 'selected="selected"';
        } ?>
                >
                    WeatherBit.io
                </option>
            </select>
            </div>
            <div><label for="unit">Unit</label></div>
            <div>
            <select id="unit" name="unit">
                <option value="CELSIUS"
                <?php if (!isset($_POST['unit']) || $_POST['unit']!=='FAHRENHEIT') {
            echo 'selected="selected"';
        } ?>  
                >
                CELSIUS
                </option>
                <option value="FAHRENHEIT"
                <?php if (isset($_POST['unit']) && $_POST['unit']==='FAHRENHEIT') {
            echo 'selected="selected"';
        } ?>
                >
                FAHRENHEIT
                </option>
            </select>
            </div>
            
                <?php
                    // display covid19 data
                    if (isset($area) && isset($area->covid19)) {
                        echo '<div class="gridtitle"><h3>Covid19 data for ' .  $area->prefecture . '</h3></div>';
                        echo '<div class="griddata"><p>Confirmed:&nbsp;</p><h4 style="color:red">' .  $area->covid19->confirmed . '</h4></div>';
                        echo '<div class="griddata"><p>Newly confirmed:&nbsp;</p><h4 style="color:red">' .  $area->covid19->newlyConfirmed . '</h4></div>';
                        echo '<div class="griddata"><p>Recovered:&nbsp;</p><h4 style="color:red">' .  $area->covid19->recovered . '</h4></div>';
                        echo '<div class="griddata"><p>Critical:&nbsp;</p><h4 style="color:red">' .  $area->covid19->critical . '</h4></div>';
                        echo '<div class="gridupdated"><p>data updated: ' .  $area->covid19->updated . '</h4></div>';
                    }
                ?>
                
            </div>
            
        </div>    
    <div>
    <br>
  </form>
</div>

<script type="text/javascript">
</script>
</body>
</html>