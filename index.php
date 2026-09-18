<?php

	if(ini_get('display_errors') == false){
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}

	if ($_SERVER['HTTP_HOST'] == 'smokeemgirl.com' || $_SERVER['HTTP_HOST'] == '://smokeemgirl.com') {
		header("Location: /conversation/");
		exit();
	}


	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$uri = 'https://';
	} else {
		$uri = 'http://';
	}
	$uri .= $_SERVER['HTTP_HOST'];
	$landingPage = file_exists('myLocalhost.php') ? 'myLocalhost.php':'index.php';

    if($landingPage){
        header('Location: '.$uri.'/'.$landingPage);
        exit;
    }else{
        echo "Something is wrong with the WebSite installation :-(";
    }


?>

