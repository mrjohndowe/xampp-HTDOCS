<?php
error_reporting(E_ALL);
	//$dir = '../.DisplayDirectoryContents';
	$fileName = 'json.json';
	//$fileName = $dir.'/'.$fileName;
	//$json = file($fileName);
	$json = file($fileName);
	
	//$json = json_decode($json);
	//echo $json[''];
	$json = $json[0];
	
	
	
	echo '<pre>';
	print_r ($json);
	echo '</pre>';
	
	
	
	
	
	
	
	
	
	function loadJSON($Obj, $json)
	{
		$dcod = json_decode($json);
		$prop = get_object_vars ( $dcod );
		foreach($prop as $key => $lock)
		{
			if(property_exists ( $Obj ,  $key ))
			{
				if(is_object($dcod->$key))
				{
					loadJSON($Obj->$key, json_encode($dcod->$key));
				}
				else
				{
					$Obj->$key = $dcod->$key;
				}
			}
		}
	}
	
	
/* 	
	foreach($json as $string){
		echo '<br>Decoding: ' . $string.'';
		json_decode($string);
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				echo ' - No errors';
			break;
			case JSON_ERROR_DEPTH:
				echo ' - Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				echo ' - Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				echo ' - Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				echo ' - Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				echo ' - Unknown error';
			break;
		}
		echo PHP_EOL;
	} */
	

?>