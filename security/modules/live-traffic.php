<?php
//Live Traffic
if ($settings['live_traffic'] == 1) {
	
	$cache_file = __DIR__ . "/cache/ip-details/". str_replace(":", "-", $ip) .".json";

    //Get Country
	if (psec_getcache($cache_file) == 'PSEC_NoCache') {
		$url = 'https://ipapi.co/' . $ip . '/json/';
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		@curl_setopt($ch, CURLOPT_REFERER, "https://google.com");
		@$curl_response = curl_exec($ch);
		curl_close($ch);
		
        $ip_data = @json_decode($curl_response);
        
		// Grabs API Response and Caches
		file_put_contents($cache_file, $curl_response);
		
	} else {
		$ip_data = @json_decode(psec_getcache($cache_file));
	}
	
	if ($ip_data && !isset($ip_data->{'error'})) {
		$country_name = $ip_data->{'country_name'};
		$country_code = $ip_data->{'country_code'};
		
		if($country_name == '') {
			$country_name = "Unknown";
			$country_code = "XX";
		}
	} else {
		$country_name = "Unknown";
		$country_code = "XX";
	}
    $bot         = 0;
    $uniquev     = 0;
    $request_uri = str_replace("'", '', $_SERVER['REQUEST_URI']);
    
    $uvcheck = $mysqli->query("SELECT ip FROM `psec_live-traffic` WHERE ip='$ip' AND useragent='$useragent' AND date='$date' LIMIT 1");
    if ($uvcheck->num_rows <= 0) {
        $uniquev = 1;
    }
    
    //Check if Bot
    if (preg_match('/bot|crawl|slurp|spider|rambler|lycos|facebookexternalhit|mediapartners|google|yahoo|yandex|bing|microsoft|facebook|robot|baidu|curl|spider/i', strtolower($useragent))) {
        $bot = 1;
    }
    
    // Get Device Type
    require 'lib/Mobile_Detect.php';
    $detect = new Mobile_DetectPSec;
    
    if ($detect->isTablet()) {
        $device_type = 'Tablet';
    } else if ($detect->isMobile()) {
        $device_type = 'Mobile';
    } else {
        $device_type = 'Computer';
    }
    
    $domain = trim($_SERVER['SERVER_NAME'], "www.");
    
    //Log Visit
    $vischeck = $mysqli->query("SELECT ip FROM `psec_live-traffic` WHERE ip='$ip' AND useragent='$useragent' AND request_uri='$request_uri' AND date='$date' AND time='$time' LIMIT 1");
    if ($vischeck->num_rows <= 0) {
        $logvisit = $mysqli->query("INSERT INTO `psec_live-traffic` (`ip`, `useragent`, `browser`, `browser_code`, `os`, `os_code`, `country`, `country_code`, `device_type`, `request_uri`, `domain`, `referer`, `bot`, `date`, `time`, `uniquev`) VALUES ('$ip', '$useragent', '$browsersh', '$browser_code', '$ossh', '$os_code', '$country_name', '$country_code', '$device_type', '$request_uri', '$domain', '$referer', '$bot', '$date', '$time', '$uniquev')");
    }
    
}
?>