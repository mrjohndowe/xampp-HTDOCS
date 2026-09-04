<link href=".global/assets/css/oaerror.css" rel="stylesheet"/>
<?php
	$self = $_SERVER['PHP_SELF'];
	$color = isset($_GET['color'])?$_GET['color']:'';
	$alpha = isset($_GET['alpha'])?$_GET['alpha']:'';
	//ini_set('allow_url_include','On');
	echo ini_get('allow_url_include');
?>
	<h1>Current Color Hex: <?=strtoupper($color)?></h1>
	<form action="<?=$self?>" method="get">
		<label for="color">Choose a color</label>
		<input type="color" id="color" name="color" placeholder="<?=$color?>" value=""/>
		<input type="range" id="alpha" name="alpha" onchange="updateColorAlpha(this.value);" min="0" max="1" step="0.01" value="1">
		<button type="submit" name="submit">SUBMIT</button>
	</form>
	
	<form action="<?=$self?>" method="get">
		<label for="color">Enter HEX Code</label>
		<input type="text" id="color" name="color" placeholder="Hex Color Code" value=""/>
		<button type="submit" name="submit">SUBMIT</button>
	</form>
	

<?php
	
if(!empty($color)){	
	
	
	$red = 256;
	$green = 256;
	$blue = 256;
	
	$rgb = ($red * 65536)+($green * 256)+$blue;
	
	$white = 255*65536+255*256+255;
	
	$blue = 0*65536+0*256+255;
	
	$red = 255*65536+0*256+0;
	
	$green = 0*65536+255*256+0;
	
	$gray = 128*65536+128*256+128;
	
	$yellow = 366*65536+255*256+0;
	
	//$hex = '#ff9900';
	$color2 = str_replace('#','',$color); 
	list($r, $g, $b) = sscanf($color2, "%02x%02x%02x");
	echo "$color2 = rgb($r,$g,$b)";
	
	
	echo '<hr>';
	
	//$color = '#ff9900';
	$rgb = hex2rgba($color);
	$rgba = hex2rgba($color, $alpha);
	/* CSS output */
	echo '
		div.example{<br>
	&emsp;	background: '.$rgb.';<br>
	&emsp;	color: '.$rgba.';<br>
		}
	';
	
	echo '
		<div style="margin-bottom:-75px;;position:relative;top:-75px;left:250px;background-color:'.$rgba.';height:100px;width:100px;border:2px solid black;"></div>
	';
	
	echo '<hr>';
	
	/* Convert hexdec color string to rgb(a) string */
}
	function hex2rgba($color, $opacity = false) {

		$default = 'rgb(0,0,0)';

		//Return default if no color provided
		if(empty($color))
			  return $default; 

		//Sanitize $color if "#" is provided 
			if ($color[0] == '#' ) {
				$color = substr( $color, 1 );
			}

			//Check if color has 6 or 3 characters and get values
			if (strlen($color) == 6) {
					$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
			} elseif ( strlen( $color ) == 3 ) {
					$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
			} else {
					return $default;
			}

			//Convert hexadec to rgb
			$rgb =  array_map('hexdec', $hex);

			//Check if opacity is set(rgba or rgb)
			if($opacity){
				if(abs($opacity) > 1)
					$opacity = 1.0;
				$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
			} else {
				$output = 'rgb('.implode(",",$rgb).')';
			}

		//Return rgb(a) color string
		return $output;
	}
	
	
	
	include_once("rgb");
?>

	<!--<iframe width="100%" height="70%" src="https://www.rapidtables.com/web/color/RGB_Color.html"></iframe>
	-->
	<script>
		function updateColorAlpha(alpha) {
			document.getElementById('color').style.opacity = alpha;
		}
	</script>