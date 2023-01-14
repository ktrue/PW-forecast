<?php header('Content-type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="Robots" content="index,nofollow" />
<title>Pirateweather Forecast script - Demo Page</title>
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
<body style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; background-color:#FFFFFF; width: 680px; margin: 0 auto;">
<?php
  if(isset($_REQUEST['sce'])) {
		print "<h2>Sorry.. the viewing of source on this demo page is not available.</h2>\n";
		print "</body></html>\n";
		exit;
	}

  $SITE = array();
	global $SITE;
	$SITE['PWAPIkey'] = 'specify-for-standalone-use-here'; // use this only for standalone 
	$SITE['charset'] = 'UTF-8';
	$SITE['lang'] = 'en';
	$SITE['PWshowUnitsAs'] = 'ca';
  $SITE['PWforecasts'] = array(
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

  include_once("PW-forecast-lang.php");
	$t = unserialize(base64_decode(TRANTABLE));
	
	$SITE['installedLanguages'] = array (
  'af' => 'Afrikaans',
  'bg' => '&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080; &#1077;&#1079;&#1080;&#1082;',
	'cs' => '&#00269;esk&yacute; jazyk',
  'ct' => 'Catal&agrave;',
  'dk' => 'Dansk',
  'nl' => 'Nederlands',
  'en' => 'English',
  'fi' => 'Suomi',
  'fr' => 'Fran&ccedil;ais',
  'de' => 'Deutsch',
  'el' => '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;',
  'hu' => 'Magyar',
  'it' => 'Italiano',
  'he' => '&#1506;&#1460;&#1489;&#1456;&#1512;&#1460;&#1497;&#1514;',
  'no' => 'Norsk',
  'pl' => 'Polski',
  'pt' => 'Portugu&ecirc;s',
  'ro' => 'limba rom&#00226;n&#00259;',
  'es' => 'Espa&ntilde;ol',
  'se' => 'Svenska',
  'si' => 'Sloven&#353;&#269;ina',
  'sk' => 'Sloven&#269;ina',
	'sr' => 'Srpski',
);

$SITE['WULanguages'] = array ( // for WeatherUnderground forecast supported languages
  'af' => 'afrikaans',
  'bg' => 'bulgarian',
  'cs' => 'czech',
  'ct' => 'catalan',
  'dk' => 'danish',
  'nl' => 'dutch',
  'en' => 'english',
  'fi' => 'finnish',
  'fr' => 'french',
  'de' => 'german',
  'el' => 'greek',
  'ga' => 'gaelic',
  'he' => 'hebrew',
  'hu' => 'hungarian',
  'it' => 'italian',
  'no' => 'norwegian',
  'pl' => 'polish',
  'pt' => 'portuguese',
  'ro' => 'romanian',
  'es' => 'espanol',
  'se' => 'swedish',
  'si' => 'slovenian',
  'sk' => 'slovak',
	'sr' => 'serbian',
);
  if(isset($_REQUEST['z']) and is_numeric($_REQUEST['z'])) {
		$doZ = $_REQUEST['z'];
		$doZ = preg_replace('|[^\d]+|Uis','',$doZ);
		$doZ = '&amp;z='.$doZ;
	} else {
		$doZ = '';
	}
  print "<h1>Demo page for PW-forecast.php</h1>\n";
	print "<p>Click on a language link to see forecast in that language</p>\n";
	print "<p>";
	$tList = '';
  foreach ($SITE['installedLanguages'] as $lang => $langName) {
		$tList .= "<a href=\"?lang=$lang$doZ\">$langName</a> | ";
	}
	$tList = substr($tList,0,strlen($tList)-3);
	print $tList;
	print "</p>\n";
	
	if(isset($_GET['lang']) and isset($SITE['installedLanguages'][$_GET['lang']])) {
		print "<h3>Forecast language '" . $SITE['installedLanguages'][$_GET['lang']] . 
		"' (" .
		ucfirst($SITE['WULanguages'][$_GET['lang']]) . 
		") shown.</h3>\n";
	} else {
		print "<h3>Forecast language 'English' shown.</h3>\n";
	}
	$doIncludePW = true;
	$doPrintPW = true;
	print "<div style=\"border: 1px solid black; margin: 5px;\">\n";
	include_once("PW-forecast.php");
	print "</div>\n";


?>
<hr />
<p>See the <a href="https://github.com/ktrue/Weather-PHP/tree/master/forecast/pirateweather">script page</a> for documentation and downloads.</p>
</body>
</html>