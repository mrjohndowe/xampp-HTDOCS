<?php
   echo "<style>body{font-size:4em;}</style>";
   session_start();
   $sec = 30;
   $self = $_SERVER['PHP_SELF'];

   header("Refresh:$sec; $self ");






  /*  $bytes = openssl_random_pseudo_bytes(255);
    $pass = bin2hex($bytes);
    echo $pass;
	exit; */
	//$pattern = '/[!-~]/'; // this would include nearly all printable ASCII characters
	//$pattern = '/[ -~\x09-\x13]/';
	//echo $pattern;

    $date = date('m/d/Y H:i:s',strtotime('NOW'));
    $date = $date . ' - ';
	$dir = 'docs';
	$fileName = 'password.txt';
	$file = $dir.'/'.$fileName;
	$file = fopen($file,'a+');
	$code = randCode() . "\r";
    fwrite($file,$date.$code);
	fclose($file);
    echo $code;

	function randCode($length = 11){
		$ranges = array(range('a', 'z'), range('A', 'Z'), range(1, 9));
		$code = '';
		for($i = 0; $i < $length; $i++){
			$rkey = array_rand($ranges);
			$vkey = array_rand($ranges[$rkey]);
			$code .= $ranges[$rkey][$vkey];
		}
         $i = 0;
        $maxNumber = 5;
		return $code;
	}
    echo '<hr>';
    $dir = 'docs';
	$fileName = 'password.txt';
	$file = $dir.'/'.$fileName;
    if ($file = fopen($file, "r")) {
    while(!feof($file)) {
        $line = fgets($file,100);
       echo ("$line") . PHP_EOL.PHP_EOL;
    }
    fclose($file);
}
?>
