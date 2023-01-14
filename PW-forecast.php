<?php 
// PW-forecast.php script by Ken True - webmaster@saratoga-weather.org
//    Forecast from pirateweather.net - based on DS-forecast.php V1.11 - 27-Dec-2022
//
// Version 1.00 - 16-Nov-2018 - initial release
// Version 1.01 - 17-Nov-2018 - added wind unit translation, fixed -0 temp display, added alerts (only English available)
// Version 1.02 - 19-Nov-2018 - added Updated: and Forecast by: display/translations
// Version 1.03 - 29-Nov-2018 - added fixes for summaries with embedded UTF symbols.
// Version 1.04 - 04-Dec-2018 - added Serbian (sr) language support
// Version 1.05 - 08-Dec-2018 - added optional current conditions display box, cloud-cover now used for better icon choices
// Version 1.06 - 05-Jan-2019 - fixed Hebrew forecast display for Saratoga template
// Version 1.07 - 07-Jan-2019 - formatting fix for Hebrew display in Saratoga template
// Version 1.08 - 15-Jan-2019 - added check for good JSON return before saving cache file
// Version 1.09 - 23-Jan-2019 - added hourly forecast and tabbed display
// Version 1.10 - 19-Jan-2022 - fix for PHP 8.1 Deprecated errata
// Version 1.11 - 27-Dec-2022 - fixes for PHP 8.2
// Version 2.00 - 14-Jan-2022 - repurposed PW-forecast.php to use Pirateweather.net API instead
//
$Version = "PW-forecast.php (ML) Version 2.00 - 14-Jan-2022";
//
// error_reporting(E_ALL);  // uncomment to turn on full error reporting
//
// script available at http://saratoga-weather.org/scripts.php
//  
// you may copy/modify/use this script as you see fit,
// no warranty is expressed or implied.
//
// This script parses the darksky.net forecast JSON API and loads icons/text into
//  arrays so you can use them in your weather website.  
//
//
// output: creates XHTML 1.0-Strict HTML page (or inclusion)
//
// Options on URL:
//
//   inc=Y            - omit <HTML><HEAD></HEAD><BODY> and </BODY></HTML> from output
//   heading=n        - (default)='y' suppress printing of heading (forecast city/by/date)
//   icons=n          - (default)='y' suppress printing of the icons+conditions+temp+wind+UV
//   text=n           - (default)='y' suppress printing of the periods/forecast text
//
//
//  You can also invoke these options directly in the PHP like this
//
//    $doIncludePW = true;
//    include("PW-forecast.php");  for just the text
//  or ------------
//    $doPrintPW = false;
//    include("PW-forecast.php");  for setting up the $PWforecast... variables without printing
//
//  or ------------
//    $doIncludePW = true;
//    $doPrintConditions = true;
//    $doPrintHeadingPW = true;
//    $doPrintIconsPW = true;
//    $doPrintTextPW = false
//    include("PW-forecast.php");  include mode, print only heading and icon set
//
// Variables returned (useful for printing an icon or forecast or two...)
//
// $PWforecastcity 		- Name of city from PW Forecast header
//
// The following variables exist for $i=0 to $i= number of forecast periods minus 1
//  a loop of for ($i=0;$i<count($PWforecastday);$i++) { ... } will loop over the available 
//  values.
//
// $PWforecastday[$i]	- period of forecast
// $PWforecasttext[$i]	- text of forecast 
// $PWforecasttemp[$i]	- Temperature with text and formatting
// $PWforecastpop[$i]	- Number - Probabability of Precipitation ('',10,20, ... ,100)
// $PWforecasticon[$i]   - base name of icon graphic to use
// $PWforecastcond[$i]   - Short legend for forecast icon 
// $PWforecasticons[$i]  - Full icon with Period, <img> and Short legend.
// $PWforecastwarnings = styled text with hotlinks to advisories/warnings
// $PWcurrentConditions = table with current conds at point close to lat/long selected
//
// Settings ---------------------------------------------------------------
// REQUIRED: a Pirateweather.net API KEY.. sign up at https://pirateweather.net
$PWAPIkey = 'specify-for-standalone-use-here'; // use this only for standalone / non-template use
// NOTE: if using the Saratoga template, add to Settings.php a line with:
//    $SITE['PWAPIkey'] = 'your-api-key-here';
// and that will enable the script to operate correctly in your template
//
$iconDir ='./forecast/images/';	// directory for carterlake icons './forecast/images/'
$iconType = '.jpg';				// default type='.jpg' 
//                        use '.gif' for animated icons fromhttp://www.meteotreviglio.com/
//
// The forecast(s) .. make sure the first entry is the default forecast location.
// The contents will be replaced by $SITE['PWforecasts'] if specified in your Settings.php

$PWforecasts = array(
 // Location|lat,long  (separated by | characters)
'Saratoga, CA, USA|37.27465,-122.02295',
'Auckland, NZ|-36.910,174.771', // Awhitu, Waiuku New Zealand
'Assen, NL|53.02277,6.59037',
'Blankenburg, DE|51.8089941,10.9080649',
'Cheyenne, WY, USA|41.144259,-104.83497',
'Carcassonne, FR|43.2077801,2.2790407',
'Braniewo, PL|54.3793635,19.7853585',
'Omaha, NE, USA|41.19043,-96.13114',
'Johanngeorgenstadt, DE|50.439339,12.706085',
'Athens, GR|37.97830,23.715363',
'Haifa, IL|32.7996029,34.9467358',
); 

//
$maxWidth = '640px';                      // max width of tables (could be '100%')
$maxIcons = 10;                           // max number of icons to display
$maxForecasts = 14;                       // max number of Text forecast periods to display
$maxForecastLegendWords = 4;              // more words in forecast legend than this number will use our forecast words 
$numIconsInFoldedRow = 8;                 // if words cause overflow of $maxWidth pixels, then put this num of icons in rows
$autoSetTemplate = true;                  // =true set icons based on wide/narrow template design
$cacheFileDir = './';                     // default cache file directory
$cacheName = "PW-forecast-json.txt";      // locally cached page from PW
$refetchSeconds = 3600;                   // cache lifetime (3600sec = 60 minutes)
//
// Units: 
// si: SI units (C,m/s,hPa,mm,km)
// ca: same as si, except that windSpeed and windGust are in kilometers per hour
// uk2: same as si, except that nearestStormDistance and visibility are in miles, and windSpeed and windGust in miles per hour
// us: Imperial units (F,mph,inHg,in,miles)
// 
$showUnitsAs  = 'ca'; // ='us' for imperial, , ='si' for metric, ='ca' for canada, ='uk2' for UK
//
$charsetOutput = 'ISO-8859-1';        // default character encoding of output
//$charsetOutput = 'UTF-8';            // for standalone use if desired
$lang = 'en';	// default language
$foldIconRow = false;  // =true to display icons in rows of 5 if long texts are found
$timeFormat = 'Y-m-d H:i T';  // default time display format

$showConditions = true; // set to true to show current conditions box

// ---- end of settings ---------------------------------------------------

// overrides from Settings.php if available
global $SITE;
if (isset($SITE['PWforecasts']))   {$PWforecasts = $SITE['PWforecasts']; }
if (isset($SITE['PWAPIkey']))	{$PWAPIkey = $SITE['PWAPIkey']; } // new V3.00
if (isset($SITE['PWshowUnitsAs'])) { $showUnitsAs = $SITE['PWshowUnitsAs']; }
if (isset($SITE['fcsticonsdir'])) 	{$iconDir = $SITE['fcsticonsdir'];}
if (isset($SITE['fcsticonstype'])) 	{$iconType = $SITE['fcsticonstype'];}
if (isset($SITE['xlateCOP']))	{$xlateCOP = $SITE['xlateCOP'];}
if (isset($LANGLOOKUP['Chance of precipitation'])) {
  $xlateCOP = $LANGLOOKUP['Chance of precipitation'];
}
if (isset($SITE['charset']))	{$charsetOutput = strtoupper($SITE['charset']); }
if (isset($SITE['lang']))		{$lang = $SITE['lang'];}
if (isset($SITE['cacheFileDir']))     {$cacheFileDir = $SITE['cacheFileDir']; }
if (isset($SITE['foldIconRow']))     {$foldIconRow = $SITE['foldIconRow']; }
if (isset($SITE['RTL-LANG']))     {$RTLlang = $SITE['RTL-LANG']; }
if (isset($SITE['timeFormat']))   {$timeFormat = $SITE['timeFormat']; }
if (isset($SITE['PWshowConditions'])) {$showConditions = $SITE['PWshowConditions'];} // new V1.05
// end of overrides from Settings.php
//
// -------------------begin code ------------------------------------------

$RTLlang = ',he,jp,cn,';  // languages that use right-to-left order

if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   
   readfile($filenameReal);
   exit;
}

$Status = "<!-- $Version on PHP ".phpversion()." -->\n";

$PWcurrentConditions = ''; // HTML for table of current conditions
//------------------------------------------------

if(preg_match('|specify|i',$PWAPIkey)) {
	print "<p>Note: the PW-forecast.php script requires an API key from Pirateweather.net to operate.<br/>";
	print "Visit <a href=\"https://www.darksky.net/account/create\">darksky.net</a> to ";
	print "register for an API key.</p>\n";
	if( isset($SITE['fcsturlPW']) ) {
		print "<p>Insert in Settings.php an entry for:<br/><br/>\n";
		print "\$SITE['PWAPIkey'] = '<i>your-key-here</i>';<br/><br/>\n";
		print "replacing <i>your-key-here</i> with your PW API key.</p>\n";
	}
	return;
}

$NWSiconlist = array(
// darksky.net ICON definitions
  'clear-day' => 'skc.jpg',
  'clear-night' => 'nskc.jpg',
  'rain' => 'ra.jpg',
  'snow' => 'sn.jpg',
  'sleet' => 'fzra.jpg',
  'wind' => 'wind.jpg',
  'fog' => 'fg.jpg',
  'cloudy' => 'ovc.jpg',
  'partly-cloudy-day' => 'sct.jpg',
  'partly-cloudy-night' => 'nsct.jpg',
  'hail' => 'ip.jpg',
  'thunderstorm' => 'tsra.jpg',
  'tornado' => 'tor.jpg'
	);
//

$windUnits = array(
 'us' => 'mph',
 'ca' => 'km/h',
 'si' => 'm/s',
 'uk2' => 'mph'
);
$UnitsTab = array(
 'si' => array('T'=>'&deg;C','W'=>'m/s','P'=>'hPa','R'=>'mm','D'=>'km'),
 'ca' => array('T'=>'&deg;C','W'=>'km/s','P'=>'hPa','R'=>'mm','D'=>'km'),
 'uk2' => array('T'=>'&deg;C','W'=>'mph','P'=>'mb','R'=>'mm','D'=>'mi'),
 'us' => array('T'=>'&deg;F','W'=>'mph','P'=>'inHg','R'=>'in','D'=>'mi'),
);

if(isset($UnitsTab[$showUnitsAs])) {
  $Units = $UnitsTab[$showUnitsAs];
} else {
	$Units = $UnitsTab['si'];
}

if(!function_exists('langtransstr')) {
	// shim function if not running in template set
	function langtransstr($input) { return($input); }
}

if(!function_exists('json_last_error')) {
	// shim function if not running PHP 5.3+
	function json_last_error() { return('- N/A'); }
	$Status .= "<!-- php V".phpversion()." json_last_error() stub defined -->\n";
	if(!defined('JSON_ERROR_NONE')) { define('JSON_ERROR_NONE',0); }
	if(!defined('JSON_ERROR_DEPTH')) { define('JSON_ERROR_DEPTH',1); }
	if(!defined('JSON_ERROR_STATE_MISMATCH')) { define('JSON_ERROR_STATE_MISMATCH',2); }
	if(!defined('JSON_ERROR_CTRL_CHAR')) { define('JSON_ERROR_CTRL_CHAR',3); }
	if(!defined('JSON_ERROR_SYNTAX')) { define('JSON_ERROR_SYNTAX',4); }
	if(!defined('JSON_ERROR_UTF8')) { define('JSON_ERROR_UTF8',5); }
}

PW_loadLangDefaults (); // set up the language defaults

if($charsetOutput == 'UTF-8') {
	foreach ($PWlangCharsets as $l => $cs) {
		$PWlangCharsets[$l] = 'UTF-8';
	}
	$Status .= "<!-- charsetOutput UTF-8 selected for all languages. -->\n";
	$Status .= "<!-- PWlangCharsets\n".print_r($PWlangCharsets,true)." \n-->\n";	
}

$PWLANG = 'en'; // Default to English for API
$lang = strtolower($lang); 	
if( isset($PWlanguages[$lang]) ) { // if $lang is specified, use it
	$SITE['lang'] = $lang;
	$PWLANG = $PWlanguages[$lang];
	$charsetOutput = (isset($PWlangCharsets[$lang]))?$PWlangCharsets[$lang]:$charsetOutput;
}

if(isset($_GET['lang']) and isset($PWlanguages[strtolower($_GET['lang'])]) ) { // template override
	$lang = strtolower($_GET['lang']);
	$SITE['lang'] = $lang;
	$PWLANG = $PWlanguages[$lang];
	$charsetOutput = (isset($PWlangCharsets[$lang]))?$PWlangCharsets[$lang]:$charsetOutput;
}

$doRTL = (strpos($RTLlang,$lang) !== false)?true:false;  // format RTL language in Right-to-left in output
if(isset($SITE['copyr']) and $doRTL) { 
 // running in a Saratoga template.  Turn off $doRTL
 $Status .= "<!-- running in Saratoga Template. doRTL set to false as template handles formatting -->\n";
 $doRTL = false;
}
if(isset($doShowConditions)) {$showConditions = $doShowConditions;}
if($doRTL) {$RTLopt = ' style="direction: rtl;"'; } else {$RTLopt = '';}; 

// get the selected forecast location code
$haveIndex = '0';
if (!empty($_GET['z']) && preg_match("/^[0-9]+$/i", htmlspecialchars($_GET['z']))) {
  $haveIndex = htmlspecialchars(strip_tags($_GET['z']));  // valid zone syntax from input
} 

if(!isset($PWforecasts[0])) {
	// print "<!-- making NWSforecasts array default -->\n";
	$PWforecasts = array("Saratoga|37.27465,-122.02295"); // create default entry
}

//  print "<!-- NWSforecasts\n".print_r($PWforecasts,true). " -->\n";
// Set the default zone. The first entry in the $SITE['NWSforecasts'] array.
list($Nl,$Nn) = explode('|',$PWforecasts[0].'|||');
$FCSTlocation = $Nl;
$PW_LATLONG = $Nn;

if(!isset($PWforecasts[$haveIndex])) {
	$haveIndex = 0;
}

// locations added to the drop down menu and set selected zone values
$dDownMenu = '';
for ($m=0;$m<count($PWforecasts);$m++) { // for each locations
  list($Nlocation,$Nname) = explode('|',$PWforecasts[$m].'|||');
  $seltext = '';
  if($haveIndex == $m) {
    $FCSTlocation = $Nlocation;
    $PW_LATLONG = $Nname;
	$seltext = ' selected="selected" ';
  }
  $dDownMenu .= "     <option value=\"$m\"$seltext>".langtransstr($Nlocation)."</option>\n";
}

// build the drop down menu
$ddMenu = '';

// create menu if at least two locations are listed in the array
if (isset($PWforecasts[0]) and isset($PWforecasts[1])) {
	$ddMenu .= '<tr align="center">
      <td style="font-size: 14px; font-family: Arial, Helvetica, sans-serif">
      <script type="text/javascript">
        <!--
        function menu_goto( menuform ){
         selecteditem = menuform.logfile.selectedIndex ;
         logfile = menuform.logfile.options[ selecteditem ].value ;
         if (logfile.length != 0) {
          location.href = logfile ;
         }
        }
        //-->
      </script>
     <form action="" method="get">
     <p><select name="z" onchange="this.form.submit()"'.$RTLopt.'>
     <option value=""> - '.langtransstr('Select Forecast').' - </option>
' . $dDownMenu .
		$ddMenu . '     </select></p>
     <div><noscript><pre><input name="submit" type="submit" value="'.langtransstr('Get Forecast').'" /></pre></noscript></div>
     </form>
    </td>
   </tr>
';
}

$Force = false;

if (isset($_REQUEST['force']) and  $_REQUEST['force']=="1" ) {
  $Force = true;
}

$doDebug = false;
if (isset($_REQUEST['debug']) and strtolower($_REQUEST['debug'])=='y' ) {
  $doDebug = true;
}
$showTempsAs = ($showUnitsAs == 'us')? 'F':'C';
$Status .= "<!-- temps in $showTempsAs -->\n";

$fileName = "https://api.pirateweather.net/forecast/$PWAPIkey/$PW_LATLONG" .
      "?exclude=minutely&lang=$PWLANG&units=$showUnitsAs";

if ($doDebug) {
  $Status .= "<!-- PW URL: $fileName -->\n";
}


if ($autoSetTemplate and isset($_SESSION['CSSwidescreen'])) {
	if($_SESSION['CSSwidescreen'] == true) {
	   $maxWidth = '900px';
	   $maxIcons = 8;
	   $maxForecasts = 8;
	   $numIconsInFoldedRow = 7;
	   $Status .= "<!-- autoSetTemplate using ".$SITE['CSSwideOrNarrowDefault']." aspect. -->\n";	
	}
	if($_SESSION['CSSwidescreen'] == false) {
	   $maxWidth = '640px';
	   $maxIcons = 8;
	   $maxForecasts = 8;
	   $numIconsInFoldedRow = 7;
	   $Status .= "<!-- autoSetTemplate using ".$SITE['CSSwideOrNarrowDefault']." aspect. -->\n";	
	}
}

$cacheName = $cacheFileDir . $cacheName;
$cacheName = preg_replace('|\.txt|is',"-$haveIndex-$showUnitsAs.txt",$cacheName); // unique cache per units used.. all are in English

$APIfileName = $fileName; 

if($showConditions) {
	$refetchSeconds = 15*60; // shorter refresh time so conditions will be 'current'
}

if (! $Force and file_exists($cacheName) and filemtime($cacheName) + $refetchSeconds > time()) {
      $html = implode('', file($cacheName)); 
      $Status .= "<!-- loading from $cacheName (" . strlen($html) . " bytes) -->\n"; 
  } else { 
      $Status .= "<!-- loading from $APIfileName. -->\n"; 
      $html = PW_fetchUrlWithoutHanging($APIfileName,false); 
	  
    $RC = '';
	if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$html,$matches)) {
	    $RC = trim($matches[1]);
	}
	$Status .= "<!-- RC=$RC, bytes=" . strlen($html) . " -->\n";
	if (preg_match('|30\d |',$RC)) { // handle possible blocked redirect
	   preg_match('|Location: (\S+)|is',$html,$matches);
	   if(isset($matches[1])) {
		  $sURL = $matches[1];
		  if(preg_match('|opendns.com|i',$sURL)) {
			  $Status .= "<!--  NOT following to $sURL --->\n";
		  } else {
			$Status .= "<!-- following to $sURL --->\n";
		
			$html = PW_fetchUrlWithoutHanging($sURL,false);
			$RC = '';
			if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$html,$matches)) {
				$RC = trim($matches[1]);
			}
			$Status .= "<!-- RC=$RC, bytes=" . strlen($html) . " -->\n";
		  }
	   }
    }
		if(preg_match('!temperature!is',$html)) {
      $fp = fopen($cacheName, "w"); 
			if (!$fp) { 
				$Status .= "<!-- unable to open $cacheName for writing. -->\n"; 
			} else {
        $write = fputs($fp, $html); 
        fclose($fp);  
			$Status .= "<!-- saved cache to $cacheName (". strlen($html) . " bytes) -->\n";
			} 
		} else {
			$Status .= "<!-- bad return from $APIfileName\n".print_r($html,true)."\n -->\n";
			if(file_exists($cacheName) and filesize($cacheName) > 3000) {
				$html = implode('', file($cacheName));
				$Status .= "<!-- reloaded stale cache $cacheName temporarily -->\n";
			} else {
				$Status .= "<!-- cache $cacheName missing or contains invalid contents -->\n";
				print $Status;
				print "<p>Sorry.. the Pirateweather forecast is not available.</p>\n";
				return;
			}
		}
} 

 $charsetInput = 'UTF-8';
  
 $doIconv = ($charsetInput == $charsetOutput)?false:true; // only do iconv() if sets are different
 if($charsetOutput == 'UTF-8') {
	 $doIconv = false;
 }
 $Status .= "<!-- using charsetInput='$charsetInput' charsetOutput='$charsetOutput' doIconv='$doIconv' doRTL='$doRTL' -->\n";
 $tranTab = PW_loadTranslate($lang);
 
  $i = strpos($html,"\r\n\r\n");
  $headers = substr($html,0,$i-1);
  $content = substr($html,$i+4);
 //  process the file .. select out the 7-day forecast part of the page
  $UnSupported = false;

// --------------------------------------------------------------------------------------------------
  
 $Status .= "<!-- processing JSON entries for forecast -->\n";
  $i = strpos($html,"\r\n\r\n");
  $headers = substr($html,0,$i-1);
  $content = substr($html,$i+4);
 

  $rawJSON = $content;
  $Status .= "<!-- rawJSON size is ".strlen($rawJSON). " bytes -->\n";

  $rawJSON = PW_prepareJSON($rawJSON);
  $JSON = json_decode($rawJSON,true); // get as associative array
  $Status .= PW_decode_JSON_error();
  $Status .= "<!-- JSON\n".print_r($JSON,true)." -->\n";
 
if(isset($JSON['daily']['data'][0]['time'])) { // got good JSON .. process it
   $UnSupported = false;

   $PWforecastcity = $FCSTlocation;
	 
   if($doIconv) {$PWforecastcity = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWforecastcity);}
   if($doDebug) {
     $Status .= "<!-- PWforecastcity='$PWforecastcity' -->\n";
   }
   //$PWtitle = langtransstr("Forecast");
	 $PWtitle = $tranTab['Pirateweather Forecast for:'];
   if($doIconv) {$PWtitle = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWtitle);}
   if($doDebug) {
     $Status .= "<!-- PWtitle='$PWtitle' -->\n";
   }

/*
    [daily] => Array
        (
            [summary] => No precipitation throughout the week, with high temperatures bottoming out at 19Â°C on Thursday.
            [icon] => clear-day
            [data] => Array
                (
                    [0] => Array
                        (
                            [time] => 1526886000
                            [summary] => Partly cloudy until afternoon.
                            [icon] => partly-cloudy-day
                            [sunriseTime] => 1526907369
                            [sunsetTime] => 1526958974
                            [moonPhase] => 0.23
                            [precipIntensity] => 0.0025
                            [precipIntensityMax] => 0.0432
                            [precipIntensityMaxTime] => 1526968800
                            [precipProbability] => 0.2
                            [precipType] => rain
                            [temperatureHigh] => 18.87
                            [temperatureHighTime] => 1526936400
                            [temperatureLow] => 10.02
                            [temperatureLowTime] => 1526997600
                            [apparentTemperatureHigh] => 18.87
                            [apparentTemperatureHighTime] => 1526936400
                            [apparentTemperatureLow] => 10.02
                            [apparentTemperatureLowTime] => 1526997600
                            [dewPoint] => 9.38
                            [humidity] => 0.73
                            [pressure] => 1011.77
                            [windSpeed] => 3.04
                            [windGust] => 22.6
                            [windGustTime] => 1526943600
                            [windBearing] => 250
                            [cloudCover] => 0.41
                            [uvIndex] => 8
                            [uvIndexTime] => 1526932800
                            [visibility] => 15.21
                            [ozone] => 374.58
                            [temperatureMin] => 11.11
                            [temperatureMinTime] => 1526907600
                            [temperatureMax] => 18.87
                            [temperatureMaxTime] => 1526936400
                            [apparentTemperatureMin] => 11.11
                            [apparentTemperatureMinTime] => 1526907600
                            [apparentTemperatureMax] => 18.87
                            [apparentTemperatureMaxTime] => 1526936400
                        )

*/
  if(isset($JSON['timezone'])) {
		date_default_timezone_set($JSON['timezone']);
		$Status .= "<!-- using '".$JSON['timezone']."' for timezone -->\n";
	}
	if(isset($JSON['daily']['data'][0]['time'])) {
		$PWupdated = $tranTab['Updated:'];
		if($doIconv) { 
		  $PWupdated = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWupdated). ' '; 
		}
		if(isset($JSON['hourly']['data'][0]['time'])) {
		  $PWupdated .= date($timeFormat,$JSON['hourly']['data'][0]['time']);
		} else {
		  $PWupdated .= date($timeFormat,$JSON['daily']['data'][0]['time']);
		}
	} else {
		$PWupdated = '';
	}
	
	if($doDebug) {
		$Status .= "\n<!-- JSON daily:data count=" . count( $JSON['daily']['data']) . "-->\n";
	}
	if(isset($windUnits[$showUnitsAs])) {
		$windUnit = $windUnits[$showUnitsAs];
		$Status .= "<!-- wind unit for '$showUnitsAs' set to '$windUnit' -->\n";
		if(isset($tranTab[$windUnit])) {
			$windUnit = $tranTab[$windUnit];
			$Status .= "<!-- wind unit translation for '$showUnitsAs' set to '$windUnit' -->\n";
		}
	} else {
		$windUnit = '';
	}

  $n = 0;
  foreach ($JSON['daily']['data'] as $i => $FCpart) {
#   process each daily entry

		list($tDay,$tTime) = explode(" ",date('l H:i:s',$FCpart['time']));
		if ($doDebug) {
				$Status .= "<!-- period $n ='$tDay $tTime' -->\n";
		}
		$PWforecastdayname[$n] = $tDay;	
		if(isset($tranTab[$tDay])) {
			$PWforecastday[$n] = $tranTab[$tDay];
		} else {
			$PWforecastday[$n] = $tDay;
		}
    if($doIconv) {
		  $PWforecastday[$n] = iconv("UTF-8",$charsetOutput.'//IGNORE',$PWforecastday[$n]);
	  }
		$PWforecasttitles[$n] = $PWforecastday[$n];
		if ($doDebug) {
				$Status .= "<!-- PWforecastday[$n]='" . $PWforecastday[$n] . "' -->\n";
		}	
		$PWforecastcloudcover[$n] = $FCpart['cloudCover'];

#  extract the temperatures

	  $PWforecasttemp[$n] = "<span style=\"color: #ff0000;\">".PW_round($FCpart['temperatureHigh'],0)."&deg;$showTempsAs</span>";
	  $PWforecasttemp[$n] .= "<br/><span style=\"color: #0000ff;\">".PW_round($FCpart['temperatureLow'],0)."&deg;$showTempsAs</span>";

#  extract the icon to use
	  $PWforecasticon[$n] = $FCpart['icon'];
	if ($doDebug) {
      $Status .= "<!-- PWforecasticon[$n]='" . $PWforecasticon[$n] . "' -->\n";
	}	

	if(isset($FCpart['precipProbability'])) {
	  $PWforecastpop[$n] = round($FCpart['precipProbability'],1)*100;
	} else {
		$PWforecastpop[$n] = 0;
	}
	if ($doDebug) {
      $Status .= "<!-- PWforecastpop[$n]='" . $PWforecastpop[$n] . "' -->\n";
	}
	
	if(isset($FCpart['precipType'])) {
		$PWforecastpreciptype[$n] = $FCpart['precipType'];
	} else {
		$PWforecastpreciptype[$n] = '';
	}


	$PWforecasttext[$n] =  // replace problematic characters in forecast text
	   str_replace(
		 array('<',   '>',  'â€“','cm.','in.','.)'),
		 array('&lt;','&gt;','-', 'cm', 'in',')'),
	   trim($FCpart['summary']));
  $tstr = $PWforecasttext[$n];
	$PWforecasttext[$n] = isset($tranTab[$tstr])?$tranTab[$tstr].'.':$tstr.'.';
	
# Add info to the forecast text
	if($PWforecastpop[$n] > 0) {
		$tstr = '';
		if(!empty($PWforecastpreciptype[$n])) {
			$t = explode(',',$PWforecastpreciptype[$n].',');
			foreach ($t as $k => $ptype) {
				if(!empty($ptype)) {$tstr .= $tranTab[$ptype].',';}
			}
			if(strlen($tstr)>0) {
				$tstr = '('.substr($tstr,0,strlen($tstr)-1) .') ';
			}
		}
		$PWforecasttext[$n] .= " ".
		   $tranTab['Chance of precipitation']." $tstr".$PWforecastpop[$n]."%. ";
	}

  $PWforecasttext[$n] .= " ".$tranTab['High:']." ".PW_round($FCpart['temperatureHigh'],0)."&deg;$showTempsAs. ";

  $PWforecasttext[$n] .= " ".$tranTab['Low:']." ".PW_round($FCpart['temperatureLow'],0)."&deg;$showTempsAs. ";

	$tWdir = PW_WindDir(round($FCpart['windBearing'],0));
  $PWforecasttext[$n] .= " ".$tranTab['Wind']." ".PW_WindDirTrans($tWdir);
  $PWforecasttext[$n] .= " ".
	     round($FCpart['windSpeed'],0)."-&gt;".round($FCpart['windGust'],0) .
	     " $windUnit.";

	if(isset($FCpart['uvIndex']) and $FCpart['uvIndex'] > 1) {
    $PWforecasttext[$n] .= " ".$tranTab['UV index']." ".round($FCpart['uvIndex'],0).".";
	}

  if($doIconv) {
		$PWforecasttext[$n] = iconv("UTF-8",$charsetOutput.'//IGNORE',$PWforecasttext[$n]);
	}

	if ($doDebug) {
      $Status .= "<!-- PWforecasttext[$n]='" . $PWforecasttext[$n] . "' -->\n";
	}

	//$temp = explode('.',$PWforecasttext[$n]); // split as sentences (sort of).
	
	$PWforecastcond[$n] = isset($tranTab[trim($FCpart['summary'])])?
	   $tranTab[trim($FCpart['summary'])]:trim($FCpart['summary']); // take first one as summary.
	if ($doDebug) {
      $Status .= "<!-- forecastcond[$n]='" . $PWforecastcond[$n] . "' -->\n";
	}

	$PWforecasticons[$n] = $PWforecastday[$n] . "<br/>" .
	     PW_img_replace(
			   $PWforecasticon[$n],$PWforecastcond[$n],$PWforecastpop[$n],$PWforecastcloudcover[$n]) . 
				  "<br/>" .
		 $PWforecastcond[$n];
	$n++;
  } // end of process text forecasts

  if(isset($JSON['flags']['sources'])) {
		$dsSources = PW_sources($JSON['flags']['sources']);
		// $Status .= "<!-- sources\n".$dsSources." -->\n";
	}
  // process alerts if any are available 
	$PWforecastwarnings = '';
  if (isset($JSON['alerts']) and is_array($JSON['alerts']) and count($JSON['alerts']) > 0) {
    $Status.= "<!-- preparing " . count($JSON['alerts']) . " warning links -->\n";
    foreach($JSON['alerts'] as $i => $ALERT) {
			$expireUTC = $ALERT['expires'];
      $expires = date('Y-m-d H:i T',$ALERT['expires']);
      $Status.= "<!-- alert expires $expires (" . $ALERT['expires'] . ") -->\n";
			$regions = '';
			if(is_array($ALERT['regions'])) {
				foreach ($ALERT['regions'] as $i => $reg) {
					$regions .= $reg . ', ';
				}
				$regions = substr($regions,0,strlen($regions)-2);
			}
					
      if (time() < $expireUTC) {
        $PWforecastwarnings .= '<a href="' . $ALERT['uri'] . '"' . ' title="' . $ALERT['title'] . " expires $expires\n$regions\n---\n" . $ALERT['description'] . '" target="_blank">' . '<strong><span style="color: red">' . $ALERT['title'] . "</span></strong></a><br/>\n";
      }
      else {
        $Status.= "<!-- alert " . $ALERT['title'] . " " . " expired - " . $ALERT['expires'] . " -->\n";
      }
    }
  }
  else {
    $Status.= "<!-- no current hazard alerts found-->\n";
  }

// make the Current conditions table from $currently array
$currently = $JSON['currently'];
/*
	"currently": {
		"time": 1543970527,
		"summary": "Overcast",
		"icon": "cloudy",
		"nearestStormDistance": 0,
		"precipIntensity": 0.127,
		"precipIntensityError": 0.1016,
		"precipProbability": 0.21,
		"precipType": "rain",
		"temperature": 12.35,
		"apparentTemperature": 12.35,
		"dewPoint": 5.48,
		"humidity": 0.63,
		"pressure": 1013.59,
		"windSpeed": 5.89,
		"windGust": 15.61,
		"windBearing": 102,
		"cloudCover": 1,
		"uvIndex": 0,
		"visibility": 14.89,
		"ozone": 311.96
	},
	"daily": {
		"summary": "Light rain today through Monday, with high temperatures rising to 16°C on Thursday.",
		"icon": "rain",
*/
$nCols = 3; // number of columns in the conditions table
	
if (isset($currently['time']) ) { // only generate if we have the data
	if (isset($currently['icon']) and ! $currently['icon'] ) { $nCols = 2; };
	
	
	$PWcurrentConditions = '<table class="PWforecast" cellpadding="3" cellspacing="3" style="border: 1px solid #909090;">' . "\n";
	
	$PWcurrentConditions .= '
  <tr><td colspan="' . $nCols . '" align="center" '.$RTLopt.'><small>' . 
  $tranTab['Currently'].': '. date($timeFormat,$currently['time']) . "<br/>\n";
	$t = $tranTab['Weather conditions at 999 from forecast point.'];
	$t = str_replace('999',round($JSON['flags']['nearest-station'],1).' '.$Units['D'],$t);
	$PWcurrentConditions .= $t .
  '</small></td></tr>' . "\n<tr$RTLopt>\n";
  if (isset($currently['icon'])) {
		$tCond = isset($tranTab[$currently['summary']])?$tranTab[$currently['summary']]:$currently['summary'];
    $PWcurrentConditions .= '
    <td align="center" valign="middle">' . 
       PW_img_replace(
			 $currently['icon'],
			 $tCond,
			 round($currently['precipProbability'],1)*100,
			 $currently['cloudCover']) . "<br/>\n" .
			 $tCond;
	$PWcurrentConditions .= '    </td>
';  
    } // end of icon
    $PWcurrentConditions .= "
    <td valign=\"middle\">\n";

	if (isset($currently['temperature'])) {
	  $PWcurrentConditions .= $tranTab['Temperature'].": <b>".
	  PW_round($currently['temperature'],0) . $Units['T'] . "</b><br/>\n";
	}
	if (isset($currently['windchill'])) {
	  $PWcurrentConditions .= $tranTab['Wind chill'].": <b>".
	  PW_round($currently['windchill'],0) . $Units['T']. "</b><br/>\n";
	}
	if (isset($currently['heatindex'])) {
	  $PWcurrentConditions .= $tranTab['Heat index'].": <b>" .
	  PW_round($currently['heatindex']) . $Units['T']. "</b><br/>\n";
	}
	if (isset($currently['windSpeed'])) {
		$tWdir = PW_WindDir(round($currently['windBearing'],0));
  $PWcurrentConditions .= $tranTab['Wind'].": <b>".PW_WindDirTrans($tWdir);
  $PWcurrentConditions .= " ".round($currently['windSpeed'],0).
	   "-&gt;".round($currently['windGust'],0) . " $windUnit." .
		"</b><br/>\n";
	}
	if (isset($currently['humidity'])) {
	  $PWcurrentConditions .= $tranTab['Humidity'].": <b>".
	  round($currently['humidity'],1)*100 . "%</b><br/>\n";
	}
	if (isset($currently['dewPoint'])) {
	  $PWcurrentConditions .= $tranTab['Dew Point'].": <b>".
	  PW_round($currently['dewPoint'],0) . $Units['T'] . "</b><br/>\n";
	}
	
	$PWcurrentConditions .= $tranTab['Barometer'].": <b>".
	PW_conv_baro($currently['pressure']) . " " . $Units['P'] . "</b><br/>\n";
	
	if (isset($currently['visibility'])) {
	  $PWcurrentConditions .= $tranTab['Visibility'].": <b>".
	  round($currently['visibility'],1) . " " . $Units['D']. "</b>\n" ;
	}

	if (isset($currently['uvIndex'])) {
	  $PWcurrentConditions .= '<br/>'.$tranTab['UV index'].": <b>".
	  round($currently['uvIndex'],0) .  "</b>\n" ;
	}
	
	$PWcurrentConditions .= '	   </td>
';
	$PWcurrentConditions .= '    <td valign="middle">
';
	if(isset($JSON['daily']['data'][0]['sunriseTime']) and 
	   isset($JSON['daily']['data'][0]['sunsetTime']) ) {
	  $PWcurrentConditions .= 
	  $tranTab['Sunrise'].': <b>'. 
		   date('H:i',$JSON['daily']['data'][0]['sunriseTime']) . 
			 "</b><br/>\n" .
		$tranTab['Sunset'].': <b>'.
	     date('H:i',$JSON['daily']['data'][0]['sunsetTime']) . 
			 "</b><br/>\n" ;
	}
	$PWcurrentConditions .= '
	</td>
  </tr>
';
  if(isset($JSON['daily']['summary'])) {
		$tCond = isset($tranTab[$JSON['daily']['summary']])?$tranTab[$JSON['daily']['summary']]:$JSON['daily']['summary'];
		if($doRTL) {
  $PWcurrentConditions .= '
	<tr><td colspan="' . $nCols . '" align="center" style="width: 350px;direction: rtl;"><small>' .
	$tCond . 
	'</small></td>
	</tr>
'; } else {
  $PWcurrentConditions .= '
	<tr><td colspan="' . $nCols . '" align="center" style="width: 350px;"><small>' .
	$tCond . 
	'</small></td>
	</tr>
';	
}
	}
  $PWcurrentConditions .= '
</table>
';
  if($doIconv) {
		$PWcurrentConditions = 
		  iconv('UTF-8',$charsetOutput.'//TRANSLIT',$PWcurrentConditions);
	}
		
} // end of if isset($currently['cityobserved'])
// end of current conditions mods

if(isset($JSON['hourly']['data'][0]['time'])) { // process Hourly forecast data
/*
	"hourly": {
		"summary": "Mostly cloudy throughout the day.",
		"icon": "partly-cloudy-night",
		"data": [{
				"time": 1548018000,
				"summary": "Mostly Cloudy",
				"icon": "partly-cloudy-day",
				"precipIntensity": 0.1422,
				"precipProbability": 0.29,
				"precipType": "rain",
				"temperature": 14.91,
				"apparentTemperature": 14.91,
				"dewPoint": 11.49,
				"humidity": 0.8,
				"pressure": 1017.89,
				"windSpeed": 10.8,
				"windGust": 24.54,
				"windBearing": 226,
				"cloudCover": 0.88,
				"uvIndex": 2,
				"visibility": 14.11,
				"ozone": 289.95
			}, {
*/
  foreach($JSON['hourly']['data'] as $i => $FCpart) {
    $PWforecasticonHR[$i] = PW_gen_hourforecast($FCpart);
		
		if($doIconv) { 
		  $PWforecasticonHR[$i]['icon'] = 
			  iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWforecasticonHR[$i]['icon']). ' '; 
		  $PWforecasticonHR[$i]['temp'] = 
			  iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWforecasticonHR[$i]['temp']). ' '; 
		  $PWforecasticonHR[$i]['wind'] = 
			  iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWforecasticonHR[$i]['wind']). ' '; 
		  $PWforecasticonHR[$i]['precip'] = 
			  iconv($charsetInput,$charsetOutput.'//TRANSLIT',$PWforecasticonHR[$i]['precip']). ' '; 
		}
		if($doDebug) {
		  $Status .= "<!-- hour $i ".$PWforecasticonHR[$i]." -->\n";
		}
		

	} // end each hourly forecast parsing
} // end process hourly forecast data
  

  
 
} // end got good JSON decode/process

// end process JSON style --------------------------------------------------------------------

// All finished with parsing, now prepare to print

  $wdth = intval(100/count($PWforecasticons));
  $ndays = intval(count($PWforecasticon)/2);
  
  $doNumIcons = $maxIcons;
  if(count($PWforecasticons) < $maxIcons) { $doNumIcons = count($PWforecasticons); }

  $IncludeMode = false;
  $PrintMode = true;

  if (isset($doPrintPW) && ! $doPrintPW ) {
      print $Status;
      return;
  }
  if (isset($_REQUEST['inc']) && 
      strtolower($_REQUEST['inc']) == 'noprint' ) {
      print $Status;
	  return;
  }

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'y') {
  $IncludeMode = true;
}
if (isset($doIncludePW)) {
  $IncludeMode = $doIncludePW;
}

$printHeading = true;
$printIcons = true;
$printText = true;

if (isset($doPrintHeadingPW)) {
  $printHeading = $doPrintHeadingPW;
}
if (isset($_REQUEST['heading']) ) {
  $printHeading = substr(strtolower($_REQUEST['heading']),0,1) == 'y';
}

if (isset($doPrintIconsPW)) {
  $printIcons = $doPrintIconsPW;
}
if (isset($_REQUEST['icons']) ) {
  $printIcons = substr(strtolower($_REQUEST['icons']),0,1) == 'y';
}
if (isset($doPrintTextPW)) {
  $printText = $doPrintTextPW;
}
if (isset($_REQUEST['text']) ) {
  $printText = substr(strtolower($_REQUEST['text']),0,1) == 'y';
}


if (! $IncludeMode and $PrintMode) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $PWtitle . ' - ' . $PWforecastcity; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charsetOutput; ?>" />
<style type="text/css">
/*--------------------------------------------------
  tabbertab 
  --------------------------------------------------*/
/* $Id: example.css,v 1.5 2006/03/27 02:44:36 pat Exp $ */

/*--------------------------------------------------
  REQUIRED to hide the non-active tab content.
  But do not hide them in the print stylesheet!
  --------------------------------------------------*/
.tabberlive .tabbertabhide {
 display:none;
}

/*--------------------------------------------------
  .tabber = before the tabber interface is set up
  .tabberlive = after the tabber interface is set up
  --------------------------------------------------*/
.tabber {
}
.tabberlive {
 margin-top:1em;
}

/*--------------------------------------------------
  ul.tabbernav = the tab navigation list
  li.tabberactive = the active tab
  --------------------------------------------------*/
ul.tabbernav
{
 margin:0 0 3px 0;
 padding: 0 3px ;
 border-bottom: 0px solid #778;
 font: bold 12px Verdana, sans-serif;
}

ul.tabbernav li
{
 list-style: none;
 margin: 0;
 min-height:40px;
 display: inline;
}

ul.tabbernav li a
{
 padding: 3px 0.5em;
	min-height: 40px;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
 margin-left: 3px;
 border: 1px solid #778;
 border-bottom: none;
 background: #DDE  !important;
 text-decoration: none !important;
}

ul.tabbernav li a:link { color: #448  !important;}
ul.tabbernav li a:visited { color: #667 !important; }

ul.tabbernav li a:hover
{
 color: #000;
 background: #AAE !important;
 border-color: #227;
}

ul.tabbernav li.tabberactive a
{
 background-color: #fff !important;
 border-bottom: none;
}

ul.tabbernav li.tabberactive a:hover
{
 color: #000;
 background: white !important;
 border-bottom: 1px solid white;
}

/*--------------------------------------------------
  .tabbertab = the tab content
  Add style only after the tabber interface is set up (.tabberlive)
  --------------------------------------------------*/
.tabberlive .tabbertab {
 padding:5px;
 border:0px solid #aaa;
 border-top:0;
	overflow:auto;

}

/* If desired, hide the heading since a heading is provided by the tab */
.tabberlive .tabbertab h2 {
 display:none;
}
.tabberlive .tabbertab h3 {
 display:none;
}
</style>	
</head>
<body style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; background-color:#FFFFFF">

<?php
} // end printmode and not includemode
print $Status;
// if the forecast text is blank, prompt the visitor to force an update
setup_tabber(); // print the tabber JavaScript so it is available

if($UnSupported) {

  print <<< EONAG
<h1>Sorry.. this <a href="https://darksky.net/forecast/$PW_LATLONG/{$showUnitsAs}12/$PWLANG">forecast</a> can not be processed at this time.</h1>


EONAG
;
}

if (strlen($PWforecasttext[0])<2 and $PrintMode and ! $UnSupported ) {

  echo '<br/><br/>'.langtransstr('Forecast blank?').' <a href="' . $PHP_SELF . '?force=1">' .
	 langtransstr('Force Update').'</a><br/><br/>';

} 
if ($PrintMode and ($printHeading or $printIcons)) { 

?>
  <table width="<?php print $maxWidth; ?>" style="border: none;" class="PWforecast">
  <?php echo $ddMenu ?>
<?php
  if ($showConditions) {
	  print "<tr><td align=\"center\">\n";
    print $PWcurrentConditions;
	  print "</td></tr>\n";
  }

?>
    <?php if($printHeading) { ?>
    <tr align="center" style="background-color: #FFFFFF;<?php 
		if($doRTL) { echo 'direction: rtl;'; } ?>">
      <td><b><?php echo $PWtitle; ?></b> <span style="color: green;">
	   <?php echo $PWforecastcity; ?></span>
     <?php if(strlen($PWupdated) > 0) {
			 echo "<br/>$PWupdated\n";
		 }
		 ?>
      </td>
    </tr>
  </table>
  <p>&nbsp;</p>
<div class="tabber" style="width: 99%; margin: 0 auto;"><!-- Day Forecast tab begin -->
  <div class="tabbertab" style="padding: 0;">
    <h2><?php 
$t = $tranTab['Daily Forecast'];
if($doIconv) { 
	$t = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$t). ' '; 
}
echo $t; ?></h2>
    <div style="width: 99%;">

  <table width="<?php print $maxWidth; ?>" style="border: none;" class="PWforecast">
	<?php } // end print heading
	
	if ($printIcons) {
	?>
    <tr>
      <td align="center">
	    <table width="100%" border="0" cellpadding="0" cellspacing="0">  
	<?php
	  // see if we need to fold the icon rows due to long text length
	  $doFoldRow = false; // don't assume we have to fold the row..
	  if($foldIconRow) {
		  $iTitleLen =0;
		  $iTempLen = 0;
		  $iCondLen = 0;
		  for($i=0;$i<$doNumIcons;$i++) {
			$iTitleLen += strlen(strip_tags($PWforecasttitles[$i]));
			$iCondLen += strlen(strip_tags($PWforecastcond[$i]));
			$iTempLen += strlen(strip_tags($PWforecasttemp[$i]));  
		  }
		  print "<!-- lengths title=$iTitleLen cond=$iCondLen temps=$iTempLen -->\n";
		  $maxChars = 135;
		  if($iTitleLen >= $maxChars or 
		     $iCondLen >= $maxChars or
			 $iTempLen >= $maxChars ) {
				 print "<!-- folding icon row -->\n";
				 $doFoldRow = true;
			 } 
			 
	  }
	  $startIcon = 0;
	  $finIcon = $doNumIcons;
	  $incr = $doNumIcons;
		$doFoldRow = false;
	  if ($doFoldRow) { $wdth = $wdth*2; $incr = $numIconsInFoldedRow; }
  print "<!-- numIconsInFoldedRow=$numIconsInFoldedRow startIcon=$startIcon doNumIcons=$doNumIcons incr=$incr -->\n";
	for ($k=$startIcon;$k<$doNumIcons-1;$k+=$incr) { // loop over icon rows, 5 at a time until done
	  $startIcon = $k;
	  if ($doFoldRow) { 
		  $finIcon = $startIcon+$numIconsInFoldedRow; 
		} else { 
		  $finIcon = $doNumIcons; 
		}
	  $finIcon = min($finIcon,$doNumIcons);
	  print "<!-- start=$startIcon fin=$finIcon num=$doNumIcons -->\n";
    print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
	  
	  for ($i=$startIcon;$i<$finIcon;$i++) {
		$ni = $doRTL?$numIconsInFoldedRow-1-$i+$startIcon+$k:$i; 
		print "<!-- doRTL:$doRTL i=$i k=$k -->\n"; 
	    print "<td style=\"width: $wdth%; text-align: center;\"><span style=\"font-size: 8pt;\">$PWforecasttitles[$ni]</span><!-- $ni '".$PWforecastdayname[$ni]."' --></td>\n";
		
	  }
	
print "          </tr>\n";	
    print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
	
	  for ($i=$startIcon;$i<$finIcon;$i++) {
		$ni = $doRTL?$numIconsInFoldedRow-1-$i+$startIcon+$k:$i;  
	    print "<td style=\"width: $wdth%;\">" . PW_img_replace($PWforecasticon[$ni],$PWforecastcond[$ni],$PWforecastpop[$ni],$PWforecastcloudcover[$ni]) . "<!-- $ni --></td>\n";
	  }
	?>
          </tr>	
	      <tr valign ="top" align="center">
	<?php
	  for ($i=$startIcon;$i<$finIcon;$i++) {
		$ni = $doRTL?$numIconsInFoldedRow-1-$i+$startIcon+$k:$i;  

	    print "<td style=\"width: $wdth%; text-align: center;\"><span style=\"font-size: 8pt;\">$PWforecastcond[$ni]</span><!-- $ni '".$PWforecastdayname[$ni]."' --></td>\n";
	  }
	
      print "	      </tr>\n";	
      print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
	  
	  for ($i=$startIcon;$i<$finIcon;$i++) {
		$ni = $doRTL?$numIconsInFoldedRow-1-$i+$startIcon+$k:$i;  
	    print "<td style=\"width: $wdth%; text-align: center;\">$PWforecasttemp[$ni]</td>\n";
	  }
	  ?>
          </tr>
	<?php if(! $iconDir) { // print a PoP row since they aren't using icons 
    print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
	
	  for ($i=$startIcon;$i<$finIcon;$i++) {
		$ni = $doRTL?$numIconsInFoldedRow-1-$i+$startIcon+$k:$i;  
	    print "<td style=\"width: $wdth%; text-align: center;\">";
	    if($PWforecastpop[$ni] > 0) {
  		  print "<span style=\"font-size: 8pt; color: #009900;\">PoP: $PWforecastpop[$ni]%</span>";
		} else {
		  print "&nbsp;";
		}
		print "</td>\n";
		
	  }
	?>
          </tr>	
	  <?php } // end if iconDir ?>
      <?php if ($doFoldRow) { 
    print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
	  
	  for ($i=$startIcon;$i<$finIcon;$i++) {
	    print "<td style=\"width: $wdth%; text-align: center;\">&nbsp;<!-- $i --></td>\n";
      
	  }
		print "</tr>\n";
      } // end doFoldRow ?>
  <?php } // end of foldIcon loop ?>
        </table><!-- end icon table -->
     </td>
   </tr><!-- end print icons -->
   	<?php } // end print icons ?>
</table>
<br/>
<?php } // end print header or icons

if ($PrintMode and $printText) { ?>
<?php
  if ($PWforecastwarnings <> '') {
		if($doIconv) { 
		  $PWforecastwarnings = 
			  iconv($charsetInput,$charsetOutput.'//IGNORE',$PWforecastwarnings); 
		}
		$tW = 'width: 640px;';
		if($doRTL) {$tW .= 'direction: rtl;';}
    print "<p class=\"PWforecast\"$tW>$PWforecastwarnings</p>\n";
  }
?>
<br/>
<table style="border: 0" width="<?php print $maxWidth; ?>" class="PWforecast">
	<?php
	  for ($i=0;$i<count($PWforecasttitles);$i++) {
        print "<tr valign =\"top\"$RTLopt>\n";
		if(!$doRTL) { // normal Left-to-right
	      print "<td style=\"width: 20%;\"><b>$PWforecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
	      print "<td style=\"width: 80%;\">$PWforecasttext[$i]</td>\n";
		} else { // print RTL format
	      print "<td style=\"width: 80%; text-align: right;\">$PWforecasttext[$i]</td>\n";
	      print "<td style=\"width: 20%; text-align: right;\"><b>$PWforecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
		}
		print "</tr>\n";
	  }
	?>
   </table>
<?php } // end print text ?>
<?php if ($PrintMode) { ?>
   </div>
 </div> <!-- end first tab --> 

  <div class="tabbertab" style="padding: 0;"><!-- begin second tab -->
    <h2><?php $t = $tranTab['Hourly Forecast'];
if($doIconv) { 
	$t = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$t). ' '; 
}
echo $t; ?></h2>
    <div style="width: 99%;">
    <table style="border: 0" width="<?php print $maxWidth; ?>" class="PWforecast">
	 <?php 
     for ($row=0;$row<4;$row++) {
       print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
			 for ($n=$row*8;$n<$row*8+8;$n++) {
		     $ni = $doRTL?($row+1)*8-$n-1+($row*8):$n;  
				 if(isset($PWforecasticonHR[$ni]['icon'])) {
					 print '<td>'.$PWforecasticonHR[$ni]['icon']."<!-- n=$n ni=$ni --></td>";;
				 } else {
					 print "<td>&nbsp;</td>";
				 }
			 }
		   print "</tr>\n";
       print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
			 for ($n=$row*8;$n<$row*8+8;$n++) {
		     $ni = $doRTL?($row+1)*8-$n-1+($row*8):$n;  
				 if(isset($PWforecasticonHR[$ni]['temp'])) {
					 print '<td>'.$PWforecasticonHR[$ni]['temp']."</td>";;
				 } else {
					 print "<td>&nbsp;</td>";
				 }
			 }
		   print "</tr>\n";
       print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
			 for ($n=$row*8;$n<$row*8+8;$n++) {
		     $ni = $doRTL?($row+1)*8-$n-1+($row*8):$n;  
				 if(isset($PWforecasticonHR[$ni]['UV'])) {
					 print '<td>'.$PWforecasticonHR[$ni]['UV']."</td>";;
				 } else {
					 print "<td>&nbsp;</td>";
				 }
			 }
		   print "</tr>\n";
       print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
			 for ($n=$row*8;$n<$row*8+8;$n++) {
		     $ni = $doRTL?($row+1)*8-$n-1+($row*8):$n;  
				 if(isset($PWforecasticonHR[$ni]['wind'])) {
					 print '<td>'.$PWforecasticonHR[$ni]['wind']."</td>";;
				 } else {
					 print "<td>&nbsp;</td>";
				 }
			 }
		   print "</tr>\n";
       print "	      <tr valign=\"top\" align=\"center\"$RTLopt>\n";
			 for ($n=$row*8;$n<$row*8+8;$n++) {
		     $ni = $doRTL?($row+1)*8-$n-1+($row*8):$n;  
				 if(isset($PWforecasticonHR[$ni]['precip'])) {
					 print '<td>'.$PWforecasticonHR[$ni]['precip']."</td>";;
				 } else {
					 print "<td>&nbsp;</td>";
				 }
			 }
		   print "</tr>\n";
			 print "<tr><td colspan=\"8\"><hr/></td></tr>\n";
		 } // end rows
?>
    </table>
    </div>
</div>
</div>
<p>&nbsp;</p>
<p><?php echo $PWforecastcity.' '; print langtransstr('forecast by');?> <a href="https://pirateweather.net/">PirateWeather.net</a>. 

<?php if($iconType == '.gif') {
	print "<br/>".langtransstr('Animated forecast icons courtesy of')." <a href=\"http://www.meteotreviglio.com/\">www.meteotreviglio.com</a>.";
}
if(strlen($dsSources) > 1) {
	print "<br/><small>".langtransstr('Sources for this forecast').": $dsSources</small>\n";
}

print "</p>\n";
 
?>
<?php
} // end printmode

 if (! $IncludeMode and $PrintMode ) { ?>
</body>
</html>
<?php 
}  

 
// Functions --------------------------------------------------------------------------------

// get contents from one URL and return as string 
function PW_fetchUrlWithoutHanging($url,$useFopen) {
  global $Status, $needCookie;
  
  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=4;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Status .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (PW-forecast.php - saratoga-weather.org)');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Status .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Status .= "<!-- Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Status .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'] .
    " dest=".$cinfo['primary_ip'] ;
	if(isset($cinfo['primary_port'])) { 
	  $Status .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Status .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Status .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Status .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Status .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Status .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Status .= "<!-- raw data\n".$data."\n -->\n"; 
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
  if($cinfo['http_code'] <> 200) {
    $Status .= "<!-- headers:\n".$headers."\n -->\n"; 
  }
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (PW-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (PW-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = PW_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = PW_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Status .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Status .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Status .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n"; 
   return($xml);
 }

}    // end PW_fetch_URL

// ------------------------------------------------------------------

function PW_fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


// -------------------------------------------------------------------------------------------
   
 function PW_img_replace ( $PWimage, $PWcondtext,$PWpop,$PWcloudcover) {
//
// optionally replace the WeatherUnderground icon with an NWS icon instead.
// 
 global $NWSiconlist,$iconDir,$iconType,$Status;
 
 $curicon = isset($NWSiconlist[$PWimage])?$NWSiconlist[$PWimage]:''; // translated icon (if any)
 	$tCCicon = PW_octets($PWcloudcover);

 if (!$curicon) { // no change.. use PW icon
   return("<img src=\"{$iconDir}na.jpg\" width=\"55\" height=\"55\" 
  alt=\"$PWcondtext\" title=\"$PWcondtext\"/>"); 
 }
 // override icon with cloud coverage octets for Images of partly-cloudy-* and clear-*
 if(preg_match('/^(partly|clear)/i',$PWimage)) {
	 $curicon = $tCCicon.'.jpg';
	 if(strpos($PWimage,'-night') !==false) {
		 $curicon = 'n'.$curicon;
	 }
	 $Status .= "<!-- using curicon=$curicon instead based on cloud coverage -->\n";
 }
 if(preg_match('/^wind/i',$PWimage) and $iconType !== '.gif') {
	 // note: Meteotriviglio icons do not have the wind_{sky}.gif icons, only wind.gif
	 $curicon = 'wind_'.$tCCicon.'.jpg';
	 if(strpos($PWimage,'-night') !==false) {
		 $curicon = 'n'.$curicon;
	 }
	 $Status .= "<!-- using curicon=$curicon instead based on cloud coverage -->\n";
 }
 
  if($iconType <> '.jpg') {
	  $curicon = preg_replace('|\.jpg|',$iconType,$curicon);
  }
  $Status .= "<!-- replace icon '$PWimage' with ";
  if ($PWpop > 0) {
	$testicon = preg_replace('|'.$iconType.'|',$PWpop.$iconType,$curicon);
		if (file_exists("$iconDir$testicon")) {
			$newicon = $testicon;
		} else {
			$newicon = $curicon;
		}
  } else {
		$newicon = $curicon;
  }
  $Status .= "'$newicon' pop=$PWpop -->\n";

  return("<img src=\"$iconDir$newicon\" width=\"55\" height=\"55\" 
  alt=\"$PWcondtext\" title=\"$PWcondtext\"/>"); 
 
 
 }

// -------------------------------------------------------------------------------------------
 
function PW_prepareJSON($input) {
	global $Status;
   
   //This will convert ASCII/ISO-8859-1 to UTF-8.
   //Be careful with the third parameter (encoding detect list), because
   //if set wrong, some input encodings will get garbled (including UTF-8!)

   list($isUTF8,$offset,$msg) = PW_check_utf8($input);
   
   if(!$isUTF8) {
	   $Status .= "<!-- PW_prepareJSON: Oops, non UTF-8 char detected at $offset. $msg. Doing utf8_encode() -->\n";
	   $str = utf8_encode($input);
       list($isUTF8,$offset,$msg) = PW_check_utf8($str);
	   $Status .= "<!-- PW_prepareJSON: after utf8_encode, i=$offset. $msg. -->\n";   
   } else {
	   $Status .= "<!-- PW_prepareJSON: $msg. -->\n";
	   $str = $input;
   }
  
   //Remove UTF-8 BOM if present, json_decode() does not like it.
   if(substr($str, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) $str = substr($str, 3);
   
   return $str;
}

// -------------------------------------------------------------------------------------------

function PW_check_utf8($str) {
// check all the characters for UTF-8 compliance so json_decode() won't choke
// Sometimes, an ISO international character slips in the PW text string.	  
     $len = strlen($str); 
     for($i = 0; $i < $len; $i++){ 
         $c = ord($str[$i]); 
         if ($c > 128) { 
             if (($c > 247)) return array(false,$i,"c>247 c='$c'"); 
             elseif ($c > 239) $bytes = 4; 
             elseif ($c > 223) $bytes = 3; 
             elseif ($c > 191) $bytes = 2; 
             else return false; 
             if (($i + $bytes) > $len) return array(false,$i,"i+bytes>len bytes=$bytes,len=$len"); 
             while ($bytes > 1) { 
                 $i++; 
                 $b = ord($str[$i]); 
                 if ($b < 128 || $b > 191) return array(false,$i,"128<b or b>191 b=$b"); 
                 $bytes--; 
             } 
         } 
     } 
     return array(true,$i,"Success. Valid UTF-8"); 
 } // end of check_utf8

// -------------------------------------------------------------------------------------------
 
function PW_decode_JSON_error() {
	
  $Status = '';
  $Status .= "<!-- json_decode returns ";
  switch (json_last_error()) {
	case JSON_ERROR_NONE:
		$Status .= ' - No errors';
	break;
	case JSON_ERROR_DEPTH:
		$Status .= ' - Maximum stack depth exceeded';
	break;
	case JSON_ERROR_STATE_MISMATCH:
		$Status .= ' - Underflow or the modes mismatch';
	break;
	case JSON_ERROR_CTRL_CHAR:
		$Status .= ' - Unexpected control character found';
	break;
	case JSON_ERROR_SYNTAX:
		$Status .= ' - Syntax error, malformed JSON';
	break;
	case JSON_ERROR_UTF8:
		$Status .= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
	break;
	default:
		$Status .= ' - Unknown error, json_last_error() returns \''.json_last_error(). "'";
	break;
   } 
   $Status .= " -->\n";
   return($Status);
}

// -------------------------------------------------------------------------------------------

function PW_fixup_text($text) {
	global $Status;
	// attempt to convert Imperial forecast temperatures to Metric in the text forecast
	
	if(preg_match_all('!([-|\d]+)([Â Âº]*F)!s',$text,$m)) {
		//$newtext = str_replace('ºF','F',$text);
		$newtext = $text;
		foreach ($m[1] as $i => $tF) {
			$tI = $m[2][$i];
			$tC = (float)(($tF - 32) / 1.8 );
			$tC = round($tC,0);
//			$newtext = str_replace("{$tF}F","{$tC}C({$tF}F)",$newtext);
			$newtext = str_replace("{$tF}{$tI}","{$tC}C",$newtext);
			$Status .= "<!-- replaced {$tF}F with {$tC}C in text forecast. -->\n";
		}
		return($newtext);
	} else {
		return($text);  // no changes
	}
	
	
}

function PW_loadLangDefaults () {
	global $PWlanguages, $PWlangCharsets;
/*
    en - [DEFAULT] English
    ar - Arabic
    az - Azerbaijani
    be - Belarusian
    bg - Bulgarian
    bs - Bosnian
    ca - Catalan
    cz - Czech
    da - Danish
    de - German
    fi - Finnish
    fr - French
    el - Greek
    et - Estonian
    hr - Croation
    hu - Hungarian
    id - Indonesian
    it - Italian
    is - Icelandic
    kw - Cornish
    lt - Lithuanian
    nb - Norwegian Bokmål
    nl - Dutch
    pl - Polish
    pt - Portuguese
    ro - Romanian
    ru - Russian
    sk - Slovak
    sl - Slovenian
    sr - Serbian
    sv - Swedish
    tr - Turkish
    uk - Ukrainian

*/
 
 $PWlanguages = array(  // our template language codes v.s. lang:LL codes for JSON
	'af' => 'en',
	'bg' => 'bg',
	'cs' => 'cs',
	'ct' => 'ca',
	'dk' => 'da',
	'nl' => 'nl',
	'en' => 'en',
	'fi' => 'fi',
	'fr' => 'fr',
	'de' => 'de',
	'el' => 'el',
	'ga' => 'en',
	'it' => 'it',
	'he' => 'he',
	'hu' => 'hu',
	'no' => 'nb',
	'pl' => 'pl',
	'pt' => 'pt',
	'ro' => 'ro',
	'es' => 'es',
	'se' => 'sv',
	'si' => 'sl',
	'sk' => 'sk',
	'sr' => 'sr',
  );

  $PWlangCharsets = array(
	'bg' => 'ISO-8859-5',
	'cs' => 'ISO-8859-2',
	'el' => 'ISO-8859-7',
	'he' => 'UTF-8', 
	'hu' => 'ISO-8859-2',
	'ro' => 'ISO-8859-2',
	'pl' => 'ISO-8859-2',
	'si' => 'ISO-8859-2',
	'sk' => 'Windows-1250',
	'sr' => 'Windows-1250',
	'ru' => 'ISO-8859-5',
  );

} // end loadLangDefaults

function PW_loadTranslate ($lang) {
	global $Status;
	
/*
Note: We packed up the translation array as it is a mix of various character set
types and editing the raw text can easily change the character presentation.
The TRANTABLE was created by using

	$transSerial = serialize($transArray);
	$b64 = base64_encode($transSerial);
	print "\n";
	$tArr = str_split($b64,72);
	print "define('TRANTABLE',\n'";
	$tStr = '';
	foreach($tArr as $rec) {
		$tStr .= $rec."\n";
	}
	$tStr = trim($tStr);
	print $tStr;
	print "'); // end of TRANTABLE encoded\n";
	
and that result included here.

It will reconstitute with unserialize(base64_decode(TRANTABLE)) to look like:
 ... 
 
 'dk' => array ( 
    'charset' => 'ISO-8859-1',
    'Sunday' => 'Søndag',
    'Monday' => 'Mandag',
    'Tuesday' => 'Tirsdag',
    'Wednesday' => 'Onsdag',
    'Thursday' => 'Torsdag',
    'Friday' => 'Fredag',
    'Saturday' => 'Lørdag',
    'Sunday night' => 'Søndag nat',
    'Monday night' => 'Mandag nat',
    'Tuesday night' => 'Tirsdag nat',
    'Wednesday night' => 'Onsdag nat',
    'Thursday night' => 'Torsdag nat',
    'Friday night' => 'Fredag nat',
    'Saturday night' => 'Lørdag nat',
    'Today' => 'I dag',
    'Tonight' => 'I nat',
    'This afternoon' => 'I eftermiddag',
    'Rest of tonight' => 'Resten af natten',
  ), // end dk 
...

and the array for the chosen language will be returned, or the English version if the 
language is not in the array.

*/
if(!file_exists("PW-forecast-lang.php")) {
	print "<p>Warning: PW-forecast-lang.php translation file was not found.  It is required";
	print " to be in the same directory as PW-forecast.php.</p>\n";
	exit;
	}
include_once("PW-forecast-lang.php");

$default = array(
    'charset' => 'ISO-8859-1',
    'Sunday' => 'Sunday',
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',
    'Sunday night' => 'Sunday night',
    'Monday night' => 'Monday night',
    'Tuesday night' => 'Tuesday night',
    'Wednesday night' => 'Wednesday night',
    'Thursday night' => 'Thursday night',
    'Friday night' => 'Friday night',
    'Saturday night' => 'Saturday night',
    'Today' => 'Today',
    'Tonight' => 'Tonight',
    'This afternoon' => 'This afternoon',
    'Rest of tonight' => 'Rest of tonight',
		'High:' => 'High:',
    'Low:' =>  'Low:',
		'Updated:' => 'Updated:',
		'Pirateweather Forecast for:' => 'Pirateweather Forecast for:',
    'NESW' =>  'NESW', // cardinal wind directions
		'Wind' => 'Wind',
    'UV index' => 'UV Index',
    'Chance of precipitation' =>  'Chance of precipitation',
		 'mph' => 'mph',
     'kph' => 'km/h',
     'mps' => 'm/s',
		 'Temperature' => 'Temperature',
		 'Barometer' => 'Barometer',
		 'Dew Point' => 'Dew Point',
		 'Humidity' => 'Humidity',
		 'Visibility' => 'Visibility',
		 'Wind chill' => 'Wind chill',
		 'Heat index' => 'Heat index',
		 'Humidex' => 'Humidex',
		 'Sunrise' => 'Sunrise',
		 'Sunset' => 'Sunset',
		 'Currently' => 'Currently',
		 'rain' => 'rain',
		 'snow' => 'snow',
		 'sleet' => 'sleet',
		 'Weather conditions at 999 from forecast point.' => 
		   'Weather conditions at 999 from forecast point.',
		 'Daily Forecast' => 'Daily Forecast',
		 'Hourly Forecast' => 'Hourly Forecast',
		 'Meteogram' => 'Meteogram',



);

 $t = unserialize(base64_decode(TRANTABLE));
 
 if(isset($t[$lang])) {
	 $Status .= "<!-- loaded translations for lang='$lang' for period names -->\n";
	 return($t[$lang]);
 } else {
	 $Status .= "<!-- loading English period names -->\n";
	 return($default);
 }
 
}
// ------------------------------------------------------------------

//  convert degrees into wind direction abbreviation   
function PW_WindDir ($degrees) {
   // figure out a text value for compass direction
// Given the wind direction, return the text label
// for that value.  16 point compass
   $winddir = $degrees;
   if ($winddir == "n/a") { return($winddir); }

  if (!isset($winddir)) {
    return "---";
  }
  if (!is_numeric($winddir)) {
	return($winddir);
  }
  $windlabel = array ("N","NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S",
	 "SSW","SW", "WSW", "W", "WNW", "NW", "NNW");
  $dir = $windlabel[ (integer)fmod((($winddir + 11) / 22.5),16) ];
  return($dir);

} // end function PW_WindDir
// ------------------------------------------------------------------

function PW_WindDirTrans($inwdir) {
	global $tranTab, $Status;
	$wdirs = $tranTab['NESW'];  // default directions
	$tstr = $inwdir;
	$Status .= "<!-- PW_WindDirTrans in=$inwdir using ";
	if(strlen($wdirs) == 4) {
		$tstr = strtr($inwdir,'NESW',$wdirs); // do translation
		$Status .= " strtr for ";
	} elseif (preg_match('|,|',$wdirs)) { //multichar translation
		$wdirsmc = explode(',',$wdirs);
		$wdirs = array('N','E','S','W');
		$wdirlook = array();
		foreach ($wdirs as $n => $d) {
			$wdirlook[$d] = $wdirsmc[$n];
		} 
		$tstr = ''; // get ready to pass once through the string
		for ($n=0;$n<strlen($inwdir);$n++) {
			$c = substr($inwdir,$n,1);
			if(isset($wdirlook[$c])) {
				$tstr .= $wdirlook[$c]; // use translation
			} else {
				$tstr .= $c; // use regular
			}
		}
		$Status .= " array substitute for ";
	}
	$Status .= "NESW=>'".$tranTab['NESW']."' output='$tstr' -->\n";

  return($tstr);
}

function PW_round($item,$dp) {
	$t = round($item,$dp);
	if ($t == '-0') {
		$t = 0;
	}
	return ($t);
}

function PW_sources ($sArray) {
	
	$lookupSources = array(
	'cmc'        => 'The USA NCEP&rsquo;s Canadian Meteorological Center ensemble model|http://nomads.ncep.noaa.gov/txt_descriptions/CMCENS_doc.shtml',
	'darksky'    => 'Dark Sky&rsquo;s own hyperlocal precipitation forecasting system, backed by radar data from the USA NOAA&rsquo;s NEXRAD system, available in the USA, and the UK Met Office&rsquo;s NIMROD system, available in the UK and Ireland.|https://darksky.net/',
	'pirateweather'    => 'PirateWeather&rsquo;s own hyperlocal precipitation forecasting system, backed by radar data from the USA NOAA&rsquo;s NEXRAD system, available in the USA, and the UK Met Office&rsquo;s NIMROD system, available in the UK and Ireland.|https://pirateweather.net/',
	'ecpa'       => 'Environment and Climate Change Canada&rsquo;s Public Alert system|https://weather.gc.ca/warnings/index_e.html',
	'gfs'        => 'The USA NOAA&rsquo;s Global Forecast System|http://en.wikipedia.org/wiki/Global_Forecast_System',
	'gefs'       => 'The Global Ensemble Forecast System (GEFS) is the ensemble version of NOAA\'s GFS model|https://www.ncei.noaa.gov/products/weather-climate-models/global-ensemble-forecast',
	'hrrr'       => 'The USA NOAA&rsquo;s High-Resolution Rapid Refresh Model|https://rapidrefresh.noaa.gov/hrrr/',
	'icon'       => 'The German Meteorological Office&rsquo;s icosahedral nonhydrostatic|https://www.dwd.de/EN/research/weatherforecasting/num_modelling/01_num_weather_prediction_modells/icon_description.html',
	'isd'        => 'The USA NOAA&rsquo;s Integrated Surface Database|https://www.ncdc.noaa.gov/isd',
	'madis'      => 'The USA NOAA/ESRL&rsquo;s Meteorological Assimilation Data Ingest System|https://madis.noaa.gov/',
	'meteoalarm' => 'EUMETNET&rsquo;s Meteoalarm weather alerting system|https://meteoalarm.eu/',
	'nam'        => 'The USA NOAA&rsquo;s North American Mesoscale Model|http://en.wikipedia.org/wiki/North_American_Mesoscale_Model',
	'nwspa'      => 'The USA NOAA&rsquo;s Public Alert system|https://alerts.weather.gov/',
	'sref'       => 'The USA NOAA/NCEP&rsquo;s Short-Range Ensemble Forecast|https://www.emc.ncep.noaa.gov/mmb/SREF/SREF.html',
	'era5'       => 'European Reanalysis 5 Dataset is used to provide historic weather data.|https://registry.opendata.aws/ecmwf-era5/',
);

	
	$outStr = '';
	foreach ($sArray as $source) {
		if(isset($lookupSources[$source])) {
			list($title,$url) = explode('|',$lookupSources[$source]);
			if(strlen($outStr) > 1) {$outStr .= ', ';}
			$outStr .= "<a href=\"$url\" title=\"$title\">".strtoupper($source)."</a>\n";
		}
	}
	return ($outStr);
}

function PW_octets ($coverage) {
	global $Status;
	
	$octets = round($coverage*100 / 12.5,1);
	$Status .= "<!-- PW_octets in=$coverage octets=$octets ";
	if($octets < 1.0) {
		$Status .= " clouds=skc -->\n";
		return('skc');
	} 
	elseif ($octets < 3.0) {
		$Status .= " clouds=few -->\n";
		return('few');
	}
	elseif ($octets < 5.0) {
		$Status .= " clouds=sct -->\n";
		return('sct');
	}
	elseif ($octets < 8.0) {
		$Status .= " clouds=bkn -->\n";
		return('bkn');
	} else {
		$Status .= " clouds=ovc -->\n";
		return('ovc');
	}
	
}

function PW_conv_baro($hPa) {
	# even 'us' imperial returns pressure in hPa so we need to convert
	global $showUnitsAs;
	
	if($showUnitsAs == 'us') {
		$t = (float)$hPa * 0.02952998751;
		return(sprintf("%01.2f",$t));
	} else {
		return( sprintf("%01.1f",$hPa) );
	}
}

function PW_gen_hourforecast($FCpart) {
	global $doDebug,$Status,$showTempsAs,$tranTab,$windUnit,$Units,$showUnitsAs;
	/* $FCpart =
	{
				"time": 1548018000,
				"summary": "Mostly Cloudy",
				"icon": "partly-cloudy-day",
				"precipIntensity": 0.1422,
				"precipProbability": 0.29,
				"precipType": "rain",
				"temperature": 14.91,
				"apparentTemperature": 14.91,
				"dewPoint": 11.49,
				"humidity": 0.8,
				"pressure": 1017.89,
				"windSpeed": 10.8,
				"windGust": 24.54,
				"windBearing": 226,
				"cloudCover": 0.88,
				"uvIndex": 2,
				"visibility": 14.11,
				"ozone": 289.95
			}
*/
  $PWH = array();
	
  //$newIcon = '<td>';
  if($showUnitsAs == 'us') {
	  $t = explode(' ',date('g:ia n/j l',$FCpart['time']));
	} else {
	  $t = explode(' ',date('H:i j/n l',$FCpart['time']));
	}
	
	$newIcon = '<b>'.$t[0].'<br/>'.$tranTab[$t[2]]."</b><br/>\n";
	
  $cloudcover = $FCpart['cloudCover'];
	if(isset($FCpart['precipProbability'])) {
	  $pop = round($FCpart['precipProbability'],1)*100;
	} else {
		$pop = 0;
	}
	$temp = explode('.',$FCpart['summary'].'.'); // split as sentences (sort of).
	
	$condition = trim($temp[0]); // take first one as summary.
	$condition = isset($tranTab[$condition])?$tranTab[$condition]:$condition;

	$icon = $FCpart['icon'];

	$newIcon .= "<br/>" .
	     PW_img_replace(
			   $icon,$condition,$pop,$cloudcover) . 
				  "<br/>" .
		 $condition;
	$PWH['icon'] = $newIcon;

	$PWH['temp'] = '<b>'.PW_round($FCpart['temperature'],0)."</b>&deg;$showTempsAs";
	$PWH['UV'] = 'UV: <b>'.$FCpart['uvIndex']."</b>";

	$tWdir = PW_WindDir(round($FCpart['windBearing'],0));
  $PWH['wind'] = $tranTab['Wind']." <b>".PW_WindDirTrans($tWdir);
  $PWH['wind'] .= " ".
	     round($FCpart['windSpeed'],0)."-&gt;".round($FCpart['windGust'],0) .
	     "</b> $windUnit\n";


	if(isset($FCpart['precipType'])) {
		$preciptype = $FCpart['precipType'];
	} else {
		$preciptype = '';
	}

	$tstr = '';
	if($pop > 0) {
		if(!empty($preciptype)) {
			$t = explode(',',$preciptype.',');
			foreach ($t as $k => $ptype) {
				if(!empty($ptype)) {$tstr .= $tranTab[$ptype].',';}
			}

			if(isset($FCpart['precipAccumulation'])) {
				$amt = $FCpart['precipAccumulation'];
				if($showUnitsAs == 'us') {
					$U   = 'in';
				} else {
					$U   = 'cm';
				}
				$accum = ' <b>' . sprintf("%01.2f",$amt)."</b>$U";
			} else {
				$accum = '';
			}
			if(strlen($tstr)>0) {
				$tstr = substr($tstr,0,strlen($tstr)-1). $accum;
			} else {
				$tstr = '&nbsp;';
			}
		}
	}
  $PWH['precip'] = "$tstr";
	
	
	

	//$newIcon .= "</td>\n";
	return($PWH);
}

function setup_tabber() {
?>	
<script type="text/javascript">
// <![CDATA[
/*==================================================
  $Id: tabber.js,v 1.9 2006/04/27 20:51:51 pat Exp $
  tabber.js by Patrick Fitzgerald pat@barelyfitz.com

  Documentation can be found at the following URL:
  http://www.barelyfitz.com/projects/tabber/

  License (http://www.opensource.org/licenses/mit-license.php)

  Copyright (c) 2006 Patrick Fitzgerald

  Permission is hereby granted, free of charge, to any person
  obtaining a copy of this software and associated documentation files
  (the "Software"), to deal in the Software without restriction,
  including without limitation the rights to use, copy, modify, merge,
  publish, distribute, sublicense, and/or sell copies of the Software,
  and to permit persons to whom the Software is furnished to do so,
  subject to the following conditions:

  The above copyright notice and this permission notice shall be
  included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
  BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
  ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
  ==================================================*/

function tabberObj(argsObj)
{
  var arg; /* name of an argument to override */

  /* Element for the main tabber div. If you supply this in argsObj,
     then the init() method will be called.
  */
  this.div = null;

  /* Class of the main tabber div */
  this.classMain = "tabber";

  /* Rename classMain to classMainLive after tabifying
     (so a different style can be applied)
  */
  this.classMainLive = "tabberlive";

  /* Class of each DIV that contains a tab */
  this.classTab = "tabbertab";

  /* Class to indicate which tab should be active on startup */
  this.classTabDefault = "tabbertabdefault";

  /* Class for the navigation UL */
  this.classNav = "tabbernav";

  /* When a tab is to be hidden, instead of setting display='none', we
     set the class of the div to classTabHide. In your screen
     stylesheet you should set classTabHide to display:none.  In your
     print stylesheet you should set display:block to ensure that all
     the information is printed.
  */
  this.classTabHide = "tabbertabhide";

  /* Class to set the navigation LI when the tab is active, so you can
     use a different style on the active tab.
  */
  this.classNavActive = "tabberactive";

  /* Elements that might contain the title for the tab, only used if a
     title is not specified in the TITLE attribute of DIV classTab.
  */
  this.titleElements = ['h2','h3','h4','h5','h6'];

  /* Should we strip out the HTML from the innerHTML of the title elements?
     This should usually be true.
  */
  this.titleElementsStripHTML = true;

  /* If the user specified the tab names using a TITLE attribute on
     the DIV, then the browser will display a tooltip whenever the
     mouse is over the DIV. To prevent this tooltip, we can remove the
     TITLE attribute after getting the tab name.
  */
  this.removeTitle = true;

  /* If you want to add an id to each link set this to true */
  this.addLinkId = false;

  /* If addIds==true, then you can set a format for the ids.
     <tabberid> will be replaced with the id of the main tabber div.
     <tabnumberzero> will be replaced with the tab number
       (tab numbers starting at zero)
     <tabnumberone> will be replaced with the tab number
       (tab numbers starting at one)
     <tabtitle> will be replaced by the tab title
       (with all non-alphanumeric characters removed)
   */
  this.linkIdFormat = '<tabberid>nav<tabnumberone>';

  /* You can override the defaults listed above by passing in an object:
     var mytab = new tabber({property:value,property:value});
  */
  for (arg in argsObj) { this[arg] = argsObj[arg]; }

  /* Create regular expressions for the class names; Note: if you
     change the class names after a new object is created you must
     also change these regular expressions.
  */
  this.REclassMain = new RegExp('\\b' + this.classMain + '\\b', 'gi');
  this.REclassMainLive = new RegExp('\\b' + this.classMainLive + '\\b', 'gi');
  this.REclassTab = new RegExp('\\b' + this.classTab + '\\b', 'gi');
  this.REclassTabDefault = new RegExp('\\b' + this.classTabDefault + '\\b', 'gi');
  this.REclassTabHide = new RegExp('\\b' + this.classTabHide + '\\b', 'gi');

  /* Array of objects holding info about each tab */
  this.tabs = new Array();

  /* If the main tabber div was specified, call init() now */
  if (this.div) {

    this.init(this.div);

    /* We don't need the main div anymore, and to prevent a memory leak
       in IE, we must remove the circular reference between the div
       and the tabber object. */
    this.div = null;
  }
}


/*--------------------------------------------------
  Methods for tabberObj
  --------------------------------------------------*/


tabberObj.prototype.init = function(e)
{
  /* Set up the tabber interface.

     e = element (the main containing div)

     Example:
     init(document.getElementById('mytabberdiv'))
   */

  var
  childNodes, /* child nodes of the tabber div */
  i, i2, /* loop indices */
  t, /* object to store info about a single tab */
  defaultTab=0, /* which tab to select by default */
  DOM_ul, /* tabbernav list */
  DOM_li, /* tabbernav list item */
  DOM_a, /* tabbernav link */
  aId, /* A unique id for DOM_a */
  headingElement; /* searching for text to use in the tab */

  /* Verify that the browser supports DOM scripting */
  if (!document.getElementsByTagName) { return false; }

  /* If the main DIV has an ID then save it. */
  if (e.id) {
    this.id = e.id;
  }

  /* Clear the tabs array (but it should normally be empty) */
  this.tabs.length = 0;

  /* Loop through an array of all the child nodes within our tabber element. */
  childNodes = e.childNodes;
  for(i=0; i < childNodes.length; i++) {

    /* Find the nodes where class="tabbertab" */
    if(childNodes[i].className &&
       childNodes[i].className.match(this.REclassTab)) {
      
      /* Create a new object to save info about this tab */
      t = new Object();
      
      /* Save a pointer to the div for this tab */
      t.div = childNodes[i];
      
      /* Add the new object to the array of tabs */
      this.tabs[this.tabs.length] = t;

      /* If the class name contains classTabDefault,
	 then select this tab by default.
      */
      if (childNodes[i].className.match(this.REclassTabDefault)) {
	defaultTab = this.tabs.length-1;
      }
    }
  }

  /* Create a new UL list to hold the tab headings */
  DOM_ul = document.createElement("ul");
  DOM_ul.className = this.classNav;
  
  /* Loop through each tab we found */
  for (i=0; i < this.tabs.length; i++) {

    t = this.tabs[i];

    /* Get the label to use for this tab:
       From the title attribute on the DIV,
       Or from one of the this.titleElements[] elements,
       Or use an automatically generated number.
     */
    t.headingText = t.div.title;

    /* Remove the title attribute to prevent a tooltip from appearing */
    if (this.removeTitle) { t.div.title = ''; }

    if (!t.headingText) {

      /* Title was not defined in the title of the DIV,
	 So try to get the title from an element within the DIV.
	 Go through the list of elements in this.titleElements
	 (typically heading elements ['h2','h3','h4'])
      */
      for (i2=0; i2<this.titleElements.length; i2++) {
	headingElement = t.div.getElementsByTagName(this.titleElements[i2])[0];
	if (headingElement) {
	  t.headingText = headingElement.innerHTML;
	  if (this.titleElementsStripHTML) {
	    t.headingText.replace(/<br>/gi," ");
	    t.headingText = t.headingText.replace(/<[^>]+>/g,"");
	  }
	  break;
	}
      }
    }

    if (!t.headingText) {
      /* Title was not found (or is blank) so automatically generate a
         number for the tab.
      */
      t.headingText = i + 1;
    }

    /* Create a list element for the tab */
    DOM_li = document.createElement("li");

    /* Save a reference to this list item so we can later change it to
       the "active" class */
    t.li = DOM_li;

    /* Create a link to activate the tab */
    DOM_a = document.createElement("a");
    DOM_a.appendChild(document.createTextNode(t.headingText));
    DOM_a.href = "javascript:void(null);";
    DOM_a.title = t.headingText;
    DOM_a.onclick = this.navClick;

    /* Add some properties to the link so we can identify which tab
       was clicked. Later the navClick method will need this.
    */
    DOM_a.tabber = this;
    DOM_a.tabberIndex = i;

    /* Do we need to add an id to DOM_a? */
    if (this.addLinkId && this.linkIdFormat) {

      /* Determine the id name */
      aId = this.linkIdFormat;
      aId = aId.replace(/<tabberid>/gi, this.id);
      aId = aId.replace(/<tabnumberzero>/gi, i);
      aId = aId.replace(/<tabnumberone>/gi, i+1);
      aId = aId.replace(/<tabtitle>/gi, t.headingText.replace(/[^a-zA-Z0-9\-]/gi, ''));

      DOM_a.id = aId;
    }

    /* Add the link to the list element */
    DOM_li.appendChild(DOM_a);

    /* Add the list element to the list */
    DOM_ul.appendChild(DOM_li);
  }

  /* Add the UL list to the beginning of the tabber div */
  e.insertBefore(DOM_ul, e.firstChild);

  /* Make the tabber div "live" so different CSS can be applied */
  e.className = e.className.replace(this.REclassMain, this.classMainLive);

  /* Activate the default tab, and do not call the onclick handler */
  this.tabShow(defaultTab);

  /* If the user specified an onLoad function, call it now. */
  if (typeof this.onLoad == 'function') {
    this.onLoad({tabber:this});
  }

  return this;
};


tabberObj.prototype.navClick = function(event)
{
  /* This method should only be called by the onClick event of an <A>
     element, in which case we will determine which tab was clicked by
     examining a property that we previously attached to the <A>
     element.

     Since this was triggered from an onClick event, the variable
     "this" refers to the <A> element that triggered the onClick
     event (and not to the tabberObj).

     When tabberObj was initialized, we added some extra properties
     to the <A> element, for the purpose of retrieving them now. Get
     the tabberObj object, plus the tab number that was clicked.
  */

  var
  rVal, /* Return value from the user onclick function */
  a, /* element that triggered the onclick event */
  self, /* the tabber object */
  tabberIndex, /* index of the tab that triggered the event */
  onClickArgs; /* args to send the onclick function */

  a = this;
  if (!a.tabber) { return false; }

  self = a.tabber;
  tabberIndex = a.tabberIndex;

  /* Remove focus from the link because it looks ugly.
     I don't know if this is a good idea...
  */
  a.blur();

  /* If the user specified an onClick function, call it now.
     If the function returns false then do not continue.
  */
  if (typeof self.onClick == 'function') {

    onClickArgs = {'tabber':self, 'index':tabberIndex, 'event':event};

    /* IE uses a different way to access the event object */
    if (!event) { onClickArgs.event = window.event; }

    rVal = self.onClick(onClickArgs);
    if (rVal === false) { return false; }
  }

  self.tabShow(tabberIndex);

  return false;
};


tabberObj.prototype.tabHideAll = function()
{
  var i; /* counter */

  /* Hide all tabs and make all navigation links inactive */
  for (i = 0; i < this.tabs.length; i++) {
    this.tabHide(i);
  }
};


tabberObj.prototype.tabHide = function(tabberIndex)
{
  var div;

  if (!this.tabs[tabberIndex]) { return false; }

  /* Hide a single tab and make its navigation link inactive */
  div = this.tabs[tabberIndex].div;

  /* Hide the tab contents by adding classTabHide to the div */
  if (!div.className.match(this.REclassTabHide)) {
    div.className += ' ' + this.classTabHide;
  }
  this.navClearActive(tabberIndex);

  return this;
};


tabberObj.prototype.tabShow = function(tabberIndex)
{
  /* Show the tabberIndex tab and hide all the other tabs */

  var div;

  if (!this.tabs[tabberIndex]) { return false; }

  /* Hide all the tabs first */
  this.tabHideAll();

  /* Get the div that holds this tab */
  div = this.tabs[tabberIndex].div;

  /* Remove classTabHide from the div */
  div.className = div.className.replace(this.REclassTabHide, '');

  /* Mark this tab navigation link as "active" */
  this.navSetActive(tabberIndex);

  /* If the user specified an onTabDisplay function, call it now. */
  if (typeof this.onTabDisplay == 'function') {
    this.onTabDisplay({'tabber':this, 'index':tabberIndex});
  }

  return this;
};

tabberObj.prototype.navSetActive = function(tabberIndex)
{
  /* Note: this method does *not* enforce the rule
     that only one nav item can be active at a time.
  */

  /* Set classNavActive for the navigation list item */
  this.tabs[tabberIndex].li.className = this.classNavActive;

  return this;
};


tabberObj.prototype.navClearActive = function(tabberIndex)
{
  /* Note: this method does *not* enforce the rule
     that one nav should always be active.
  */

  /* Remove classNavActive from the navigation list item */
  this.tabs[tabberIndex].li.className = '';

  return this;
};


/*==================================================*/


function tabberAutomatic(tabberArgs)
{
  /* This function finds all DIV elements in the document where
     class=tabber.classMain, then converts them to use the tabber
     interface.

     tabberArgs = an object to send to "new tabber()"
  */
  var
    tempObj, /* Temporary tabber object */
    divs, /* Array of all divs on the page */
    i; /* Loop index */

  if (!tabberArgs) { tabberArgs = {}; }

  /* Create a tabber object so we can get the value of classMain */
  tempObj = new tabberObj(tabberArgs);

  /* Find all DIV elements in the document that have class=tabber */

  /* First get an array of all DIV elements and loop through them */
  divs = document.getElementsByTagName("div");
  for (i=0; i < divs.length; i++) {
    
    /* Is this DIV the correct class? */
    if (divs[i].className &&
	divs[i].className.match(tempObj.REclassMain)) {
      
      /* Now tabify the DIV */
      tabberArgs.div = divs[i];
      divs[i].tabber = new tabberObj(tabberArgs);
    }
  }
  
  return this;
}


/*==================================================*/


function tabberAutomaticOnLoad(tabberArgs)
{
  /* This function adds tabberAutomatic to the window.onload event,
     so it will run after the document has finished loading.
  */
  var oldOnLoad;

  if (!tabberArgs) { tabberArgs = {}; }

  /* Taken from: http://simon.incutio.com/archive/2004/05/26/addLoadEvent */

  oldOnLoad = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = function() {
      tabberAutomatic(tabberArgs);
    };
  } else {
    window.onload = function() {
      oldOnLoad();
      tabberAutomatic(tabberArgs);
    };
  }
}

/*==================================================*/

/* Run tabberAutomaticOnload() unless the "manualStartup" option was specified */

if (typeof tabberOptions == 'undefined') {

    tabberAutomaticOnLoad();

} else {

  if (!tabberOptions['manualStartup']) {
    tabberAutomaticOnLoad(tabberOptions);
  }

}
// ]]>
</script>
<?php // end tabber JS
} 
// End of functions --------------------------------------------------------------------------
