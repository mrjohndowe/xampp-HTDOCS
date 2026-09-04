<?php
	echo '<link href="css/license.css" rel="stylesheet"/>';
	echo '<link href="https://fonts.googleapis.com/css?family=Libre Barcode 39" rel="stylesheet">';
	require_once '../.global/extra/functions.php';
	//header("Content-Type: image/png");
	
	$names = $r->generateNames(1);
	$names = $names[0];
	$fName = $names['first_name'];
	$lName = $names['last_name'];
	
	
	
	$info = [
		'CARD_TITLE' => 'Department of Thirst',
		'PAGE_TITLE' => 'Horny License',
		'FIRST_NAME' => "$fName",
		'LAST_NAME' => "$lName",
		'USER_NAME' => "$fName $lName",
		'USER_SEX' => 'Yes Please!',
		'USER_CLASS' => 'HORNY',
		'USER_TYPE' => 'Redhead, Fishnet Stockings, Pregnant'
	];
	foreach($info as $name => $val){
		define($name,$val);
	}
	$imageFolder = 'extra/images/portraits/';
	function getImage(){
		GLOBAL $imageFolder;
		$files = scandir($imageFolder);
		$fileCount = getFileCount($imageFolder) +1;
		$link = $files[rand(2,$fileCount)];
		$link = $imageFolder.$link;
		return $link;
	}
	$url = $_SERVER['PHP_SELF'];
	$sec = 10;
	header("Refresh:$sec; url=$url");
	
	$idCode = strtoupper(randCode());
	$idCode = implode("-",str_split($idCode,4));
	
	$title = '<title>'. PAGE_TITLE .'</title>';
	echo $title;
	$header = str_replace('title','h1',$title);
?>
<!--<div class="container2 blood"><?=$header?></div>-->
<?php echo str_repeat('<div class="container"></div>', 3); ?>
<hr class="white">
<div class="page-wrap">
	
	<div class="cardContainer Seigaiha">
		<span class="cardTitle"><?=CARD_TITLE?></span>
		<div class="photo"><img class="photo2" src="<?=getImage()?>"/></div>
		<div class="titleBanner">License to be<br>horny all day</div>
		<div class="id">ID: <?=$idCode?></div>
		<div class="name">Name: <?=USER_NAME?></div>
		<div class="sex">Sex: <?=USER_SEX?></div>
		<div class="class">Class: <?=USER_CLASS?></div>
		<div class="type">Type: <?=USER_TYPE?></div>
		<div class="signatureBlock"><span class="signature"><?=LAST_NAME?></span></div>
		<div class="barcode"><?=randCode()?></div>
		<img class="cup" src="extra/images/cup.png"/>
		<img class="heart" src="extra/images/heart.png"/>
	</div>
</div>
<div class="container"><hr class="white"></div>
<footer><h1 class="bloody"><?=USER_NAME?></h1></footer>
<!--
<hr>

<center>
<footer><img style="width:25%;" src="templates/license.jpg"/></footer>
</center>-->
<?php
	
	function randCode($length = 11){
		$ranges = array(range('a', 'z'), range('A', 'Z'), range(1, 9));
		$code = '';
		for($i = 0; $i < $length; $i++){
			$rkey = array_rand($ranges);
			$vkey = array_rand($ranges[$rkey]);
			$code .= $ranges[$rkey][$vkey];
		}
		return $code;
	}

?>