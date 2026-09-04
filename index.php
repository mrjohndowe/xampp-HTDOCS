<?php

	if(ini_get('display_errors') == false){
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$uri = 'https://';
	} else {
		$uri = 'http://';
	}
	$uri .= $_SERVER['HTTP_HOST'];
	$landingPage = file_exists('myLocalhost.php') ? 'myLocalhost.php':'index.php';
	header('Location: '.$uri.'/'.$landingPage);
	exit;


?>
Something is wrong with the XAMPP installation :-(
