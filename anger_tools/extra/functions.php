<?php
// Make sure use_strict_mode is enabled.
// use_strict_mode is mandatory for security reasons.
//ini_set('session.use_strict_mode', 1);
//ini_set('session.auto_start',0);
my_session_start();
$genDB = new passDataBase;
//$genSalt = md5(microtime());
//$genPass = md5(md5($username.$password).$genSalt);
//$crypto = md5(md5($username.$password));

class passDataBase
{
	public function encodeDB($username, $password, $b32)
	{
		global $db;

		$password = $username . $password . $b32;
		$password = bin2hex($password);
		return ($password);
	}

	public function decodeDB($username, $password, $encoded)
	{
		global $db;

		$prefix = $username . $password;
		$password = strstr(hex2bin($encoded), $prefix);

		return ($password);
	}
}

function display($name)
{
	global $db;

	$q = 'SELECT name FROM users WHERE username = :username';
	$p = [':username' => $name];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);

	return $stmt->fetch()['name'];
}
function getName($id)
{
	global $db;

	$q = 'SELECT name FROM users WHERE id = :id';
	$p = [':id' => $id];
	$stmt = $db->prepare($q);
	$results = $stmt->execute($p);
	return $stmt->fetch()['name'];
}
function angerCode($id)
{
	global $db;

	$q = 'SELECT keycode FROM anger WHERE id = :id';
	$p = [':id' => $id];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	return $stmt->fetch()['keycode'];
}
function getCode($id)
{
	global $db;

	$q = 'SELECT salt FROM users WHERE id = :id';
	$p = [':id' => $id];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	return $stmt->fetch()['salt'];
}

function getUserID($name)
{
	global $db;
	$q = 'SELECT id FROM users WHERE username = :username';
	$p = [':username' => $name];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);

	return $stmt->fetch()['id'];
}

function secureDB($uName)
{
	global $db;

	$q = 'SELECT salt FROM users WHERE username = :username';
	$p = [':username' => $uName];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	return $stmt->fetch()['salt'];
}

function checkUser($name)
{
	global $db;

	$q = 'SELECT * FROM users WHERE username = :user';
	$p = [':user' => $name];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$name = $stmt->fetch()['username'];
	$name = !empty($name) ? true : false;
	return $name;
}
function checkEmail($name)
{
	global $db;

	$q = 'SELECT * FROM users WHERE email = :email';
	$p = [':email' => $name];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$name = $stmt->fetch()['email'];
	$name = !empty($name) ? true : false;
	return $name;
}
function checkLogin($username, $password)
{
	global $db;

	$q = 'SELECT * FROM users WHERE username = :username';
	$p = [':username' => $username];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$info = $stmt->fetch();
	$pass = $info['password'];
	$salt = $info['salt'];



	$loginOK = password_verify($password, $pass) ? $_SESSION['user'] = $username : false;
	return $loginOK;
}
function getAngerCount($name)
{
	global $db;

	$month = date('m');
	$year = date('Y');
	//MONTH(curdate())

	$q = 'SELECT count(id)as angerCount FROM anger WHERE month = :month AND year = :year AND username = :name';
	$p = [':month' => $month, ':year' => $year, ':name' => $name];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$count = $stmt->fetch()['angerCount'];
	return $count;
}
function MONTH()
{
	$currentMonth = date('m');
	return $currentMonth;
}
function YEAR()
{
	$currentYear = date('Y');
	return $currentYear;
}

function getMonth($timestamp)
{
	$month = strtotime(date('m'), $timestamp);
	return $month;
}
function getDay($timestamp)
{
	$day = strtotime(date('d'), $timestamp);
	return $day;
}
function getYear($timestamp)
{
	$year = strtotime(date('Y'), $timestamp);
	return $year;
}
function getHour($timestamp)
{
	$hour = strtotime(date('H'), $timestamp);
	return $hour;
}
function getMin($timestamp)
{
	$min = strtotime(date('i'), $timestamp);
	return $min;
}
/* function radioHtml($html, $times){
		$i = 0;
		for($i <= $times){
			echo $html;
			$i = $i++;
		}

	} */
//( 1 = not at all; 5 = medium; 10 = very, very angry)
function getAngerLevel($numb)
{
	switch ($numb) {
		case 'option1':
			$level = 'rgb(21, 234, 0)';

			break;
		case 'option2':
			$level = 'rgb(45, 209, 0)';

			break;
		case 'option3':
			$level = 'rgb(69, 185, 0)';

			break;
		case 'option4':
			$level = 'rgb(93, 163, 0)';

			break;
		case 'option5':
			$level = 'rgb(116, 139, 0)';

			break;
		case 'option6':
			$level = 'rgb(141, 115, 0)';

			break;
		case 'option7':
			$level = 'rgb(164, 91, 0)';

			break;
		case 'option8':
			$level = 'rgb(187, 67, 0)';

			break;
		case 'option9':
			$level = 'rgb(211, 45, 0)';

			break;
		case 'option10':
			$level = 'rgb(241, 14, 0)';

			break;
		default:
			$level = 'white';
			$levelScale = '';
	}
	return $level;
}

function getAngerLevelScale($numb)
{
	switch ($numb) {
		case 'option1':
			$levelScale = 'Calm';
			break;
		case 'option2':
			$levelScale = 'Slightly Annoyed';
			break;
		case 'option3':
			$levelScale = 'Mildly Irritated';
			break;
		case 'option4':
			$levelScale = 'Frustrated';
			break;
		case 'option5':
			$levelScale = 'Visibly Upset';
			break;
		case 'option6':
			$levelScale = 'Angry';
			break;
		case 'option7':
			$levelScale = 'Very Angry';
			break;
		case 'option8':
			$levelScale = 'Furious';
			break;
		case 'option9':
			$levelScale = 'Enraged';
			break;
		case 'option10':
			$levelScale = 'Consumed by Rage';
			break;
		default:
			$levelScale = '';
	}
	return $levelScale;
}

function auto_copyright($year = 'auto')
{
	if (intval($year) == 'auto') {
		$year = date('Y');
	}
	if (intval($year) == date('Y')) {
		echo intval($year);
	}
	if (intval($year) < date('Y')) {
		echo intval($year) . ' - ' . date('Y');
	}
	if (intval($year) > date('Y')) {
		echo date('Y');
	}
}
function redirect($link = '', $popup = null)
{
	// set default link
	$link = ($link == '') ? 'index.php' : $link;

	// create new location link
	$location = 'location: ';
	$location .= $link;

	// check states to see if it should redirect or echo debug data
	if ($popup === true || (!empty($GLOBALS['devEnableRedirectConfirm']) && $popup !== false)) {
		//echo '<script>alert("Header Redirect Debug:\n'.$location.'");</script>';
		echo '<script>
				function continueRedirect(){
					var r = confirm("Click OK to proceed with redirect\n'
			. '\n' . $location . '");
					if (r==true){
						window.location.replace("' . $link . '");
					}
				}
				continueRedirect();
			</script>';
		exit();
	}

	// debugging is disabled or bypassed
	header($location);
	exit();
}
function logout()
{
	unset($_SESSION['user']);
	unset($_SESSION['deleted_time']);
	session_destroy();
}
// My session start function support timestamp management
function my_session_start()
{
	//session_start();
	// Do not allow to use too old session ID
	if (!empty($_SESSION['deleted_time']) && $_SESSION['deleted_time'] < time() - 180) {
		session_destroy();
		session_start();
	}
}

// My session regenerate id function
function my_session_regenerate_id()
{
	// Call session_create_id() while session is active to
	// make sure collision free.
	if (session_status() != PHP_SESSION_ACTIVE) {
		session_start();
	}
	// WARNING: Never use confidential strings for prefix!
	$newid = session_create_id('myprefix-');
	// Set deleted timestamp. Session data must not be deleted immediately for reasons.
	$_SESSION['deleted_time'] = time();
	// Finish session
	session_commit();
	// Make sure to accept user defined session ID
	// NOTE: You must enable use_strict_mode for normal operations.
	ini_set('session.use_strict_mode', 0);
	// Set new custom session ID
	session_id($newid);
	// Start with custom session ID
	session_start();
}



function initialize($user, $logout = false)
{

	// Session ID must be regenerated when
	//  - User logged in
	//  - User logged out
	//  - Certain period has passed
	my_session_regenerate_id();

	// Write useful codes

}

function base32_decode($b32)
{
	$lut = array(
		"A" => 0,
		"B" => 1,
		"C" => 2,
		"D" => 3,
		"E" => 4,
		"F" => 5,
		"G" => 6,
		"H" => 7,
		"I" => 8,
		"J" => 9,
		"K" => 10,
		"L" => 11,
		"M" => 12,
		"N" => 13,
		"O" => 14,
		"P" => 15,
		"Q" => 16,
		"R" => 17,
		"S" => 18,
		"T" => 19,
		"U" => 20,
		"V" => 21,
		"W" => 22,
		"X" => 23,
		"Y" => 24,
		"Z" => 25,
		"2" => 26,
		"3" => 27,
		"4" => 28,
		"5" => 29,
		"6" => 30,
		"7" => 31
	);
	$b32 = strtoupper($b32);
	$l = strlen($b32);
	$n = 0;
	$j = 0;
	$binary = "";
	for ($i = 0; $i < $l; $i++) {
		$n = $n << 5;
		$n = $n + $lut[$b32[$i]];
		$j = $j + 5;
		if ($j >= 8) {
			$j = $j - 8;
			$binary .= chr(($n & (0xFF << $j)) >> $j);
		}
	}
	return $binary;
}

function binary_encoded($para)
{
	$lut = [
		'a' => "01100001",
		'A' => "01000001",
		'b' => "01100010",
		'B' => "01000010",
		'c' => "01100011",
		'C' => "01000011",
		'd' => "01100100",
		'D' => "01000100",
		'e' => "01100101",
		'E' => "01000101",
		'f' => "01100110",
		'F' => "01000110",
		'g' => "01100111",
		'G' => "01000111",
		'h' => "01101000",
		'H' => "01001000",
		'i' => "01101001",
		'I' => "01001001",
		'j' => "01101010",
		'J' => "01001010",
		'k' => "01101011",
		'K' => "01001011",
		'l' => "01101100",
		'L' => "01001100",
		'm' => "01101101",
		'M' => "01001101",
		'n' => "01101110",
		'N' => "01001110",
		'o' => "01101111",
		'O' => "01001111",
		'p' => "01110000",
		'P' => "01010000",
		'q' => "01110001",
		'Q' => "01010001",
		'r' => "01110010",
		'R' => "01010010",
		's' => "01110011",
		'S' => "01010011",
		't' => "01110100",
		'T' => "01010100",
		'u' => "01110101",
		'U' => "01010101",
		'v' => "01110110",
		'V' => "01010110",
		'w' => "01110111",
		'W' => "01010111",
		'x' => "01111000",
		'X' => "01011000",
		'y' => "01111001",
		'Y' => "01011001",
		'z' => "01111010",
		'Z' => "01011010",
		' ' => "00000000",
		'.' => "........",
		'?' => "????????"
	];

	$l = strlen($para);
	$n = 0;
	$j = 0;
	$binary = "";
	$letters = str_split($para);
	foreach ($letters as $letter) {
		//$binary .=  $n++ .') '. $letter .'->'.$lut[$letter].'<br>';
		$binary .= $lut[$letter];
	}

	return $binary;
}

function binary_decoded($para)
{
	$lut = [
		'01100001' =>	'a',
		'01000001' => 'A',
		'01100010' =>	'b',
		'01000010' => 'B',
		'01100011' =>	'c',
		'01000011' => 'C',
		'01100100' =>	'd',
		'01000100' => 'D',
		'01100101' =>	'e',
		'01000101' => 'E',
		'01100110' =>	'f',
		'01000110' => 'F',
		'01100111' =>	'g',
		'01000111' => 'G',
		'01101000' =>	'h',
		'01001000' => 'H',
		'01101001' =>	'i',
		'01001001' => 'I',
		'01101010' =>	'j',
		'01001010' => 'J',
		'01101011' =>	'k',
		'01001011' => 'K',
		'01101100' =>	'l',
		'01001100' => 'L',
		'01101101' =>	'm',
		'01001101' => 'M',
		'01101110' =>	'n',
		'01001110' => 'N',
		'01101111' =>	'o',
		'01001111' => 'O',
		'01110000' =>	'p',
		'01010000' => 'P',
		'01110001' =>	'q',
		'01010001' => 'Q',
		'01110010' =>	'r',
		'01010010' => 'R',
		'01110011' =>	's',
		'01010011' => 'S',
		'01110100' =>	't',
		'01010100' => 'T',
		'01110101' =>	'u',
		'01010101' => 'U',
		'01110110' =>	'v',
		'01010110' => 'V',
		'01110111' =>	'w',
		'01010111' => 'W',
		'01111000' =>	'x',
		'01011000' => 'X',
		'01111001' =>	'y',
		'01011001' => 'Y',
		'01111010' =>	'z',
		'01011010' => 'Z',
		'00000000' => ' ',
		'????????' => '?',
		'........' => '.'
	];

	$l = strlen($para);
	$n = 0;
	$j = 0;
	$binary = "";
	$letters = str_split($para, 8);
	foreach ($letters as $letter) {
		$binary .= $lut[$letter];
	}

	return $binary;
}

function getVersionNumber()
{
	$v1 = rand(1, 99);
	$v2 = rand(1, 99);
	$v3 = rand(1, 99);
	$versionNumber = $v1 . '.' . $v2 . '.' . $v3;

	return $versionNumber;
}
function console_log($data)
{

	$display = '<script>';
	$display .= 'console.log(' . json_encode($data) . ');';
	$display .= '</script>';
	echo $display;
}
