<div class="container">
<?php
	$links = [
		'Desktop SSM' => 'http://178.128.134.216:3000',
		'Colorado Works Program' => 'https://www.connectingcolorado.com/',
		'Desktop FiveM Server' => 'https://servers.fivem.net/servers/detail/7kjpzb',
		'Desktop TXAdmin' => 'http://gamingdesktop.com:40120/auth',
		'Fedex Tracking' => 'https://www.fedex.com/fedextrack/summary?trknbr=523278488934,523278482248',
		'Cfx.re Keymaster' => 'https://keymaster.fivem.net/',
		'Pornhub' => 'https://pornhub.com/',
		'FiveM CAD' => 'https://cad.xlntransport.com/',
		'Plus 2 Net' => 'https://www.plus2net.com/php_tutorial/php_for_loop.php',
		//'404' => 'https://gamingdesktop.com/404',
		'Discord Developer Portal' => 'https://discord.com/developers/applications',
		'Digital Ocean' => 'https://cloud.digitalocean.com/login',
		'XLNT Admin' => 'https://xlntransport.com:8083',
		'XLNT Webmail' => 'https://webmail.xlntransport.com',
		'XLNT PHPMyAdmin' => 'https://xlntransport.com/phpmyadmin',
		'XLNT TXAdmin' => 'http://206.81.7.154:40120/',
		'Youtube' => 'https://youtu.be/f_mNOgyCn-k',
		
		
	];
	
	$i = 0;
	foreach($links as $name => $link){
		$i = $i+1;

		$show = /* $i . */' <a href="'.$link.'" target="_blank">'.$name.'</a>';
		if($i == 10){
			$show .= '<br>';
		}else{
			$show .= '<strong style="color:red;"> |:-:| </strong>';
		}
		echo $show;
	}
	echo '<hr>
		<div class="oaerror alert danger">LINKS</div>
	';


?>

</div>