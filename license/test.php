<div id="page-wrap">
<?php
	
	//set_include_path( get_include_path() . PATH_SEPARATOR . dirname ( __DIR__ ) . '/insurance/' );

	/* $cssFolder = 'css/';
	$files = scandir($cssFolder);
	
	foreach($files as $file){
		$link = $cssFolder.$file;
		if($file == '.' || $file == '..')continue;
		echo '<link href="'.$link.'" ref="stylesheet" /><p>'.$link.'</p>';
	}  */
	$cssFolder = "css/";
	$files = scandir($cssFolder,0);
	
	foreach($files as $file){
		if($file == '.' || $file == '..')continue;
		$link = $cssFolder.$file;
		echo '<link href="'.$link.'" rel="stylesheet"/>';
	}
	
	$jsFolder = "js/";
	$files = scandir($jsFolder,1);
	
	foreach($files as $file){
		if($file == '.' || $file == '..')continue;
		$link = $jsFolder.$file;
		echo '<script src="'.$link.'"></script>';
	}

	$cookieName = 'progress';
	$cookie = !empty($_COOKIE[$cookieName])?$_COOKIE[$cookieName]:0;
	$sec = 10;
	$max = 150;
	$url = $_SERVER['PHP_SELF'];
		
	$image = 'license';
	$pageHeader = "<title>$image | $cookie/$max</title>";
	
	if($cookie != $max){
		header("Refresh:$sec; url=$url");
		$cookie = $cookie +1;
		setcookie($cookieName, $cookie, time()+3600);
	}else{
		setcookie($cookieName,0,time() - 3600);
		echo '<a href="'.$url.'" target="_parent">Restart</a><hr>
			<div class="meter">
				<span width="100%">Completed</span>
			</div>		
		';
		
		die();
		
	}
	$head = str_replace('title','h1',$pageHeader);
	echo $pageHeader;
	
	$percent = ($_COOKIE[$cookieName] / $max) * 100;
	
?>

		<?=$head?>
		
		<div class="meter red">
			<span style="width: <?=$percent?>%;"><?=$percent?>%</span>
		</div>
	</div>
	<div>
		<img style="width:30%;" name="template" class="alert danger" src="templates/<?=$image?>.jpg"/>
		<span style="width:30%;" name="card" class="alert info"><?include_once $image.'.php';?></span>
		<!--<img name="card" class="alert info" src="<?=$image?>.php"/>-->
	</div>
	
	<footer class="site-footer"><?php include_once 'extra/footer.php';?></footer>