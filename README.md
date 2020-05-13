## Weather forecast 
This program is Weather forecast web application for coding assignment.
You can redistribute it and/or modify except api keys.
 
## Flow

- Search location by postcode (japan only) using Geocoding API Google/Yahoo 
- Get weather forecast data by location coordinate using API of OpenWeatherMap.org/WeatherBit.io
- Get Covid19 statistic data for related prefecture
- Display Forecast, Map and Covid19 data  

## Used APIs :  
  
       Google Maps Platform -> Web services -> Geocoding API
       https://developers.google.com/maps/documentation/geocoding/intro
       Provide Location name in English
 
       Google Maps Platform -> Web -> Maps Static API
       https://developers.google.com/maps/documentation/maps-static/dev-guide
 
       Google Maps Platform -> Web -> Maps Embed API
       https://developers.google.com/maps/documentation/embed/guide
 
       Yahoo! Geocoder API 
       https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/geocoder.html
       Provide Location name in Japanese
  
       Open Weather -> Weather API
       https://openweathermap.org/forecast5
       Provide 5 days forecast by every 3 hours record
  
       Weatherbit.io -> Weather API
       https://www.weatherbit.io/api/weather-forecast-16-day
       Provide 16 days forecast by daily
  
       Japan COVID-19 Coronavirus Tracker - https://covid19japan.com/
       https://github.com/reustle/covid19japan-data/

### Requirement

Web server with PHP 7.3
