<?php
/*    $username = "register"; 
    $password = "gML13RsNREwtOdSH"; 
    $host = "localhost"; 
    $dbname = "register"; */
	
    //DATABASE CONNECTION INFORMATION
	
	$connection = [
		'db_user' => "root",
		'db_pass' => "",
		'db_host' => "localhost",
		'db_data' => "register2"
	];
	// echo '<u>SOLO</u><br>';	
	foreach($connection as $name => $value) {
		// echo $name .': '.$value . '<br>';
		define($name,$value);
	}
	
	$connection = array(
		db_user,db_pass,db_host,db_data
	);
	
	$options = array (
		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
	);
	
	try {
		$db = new PDO(
			"mysql:host={$connection[2]};charset=utf8", $connection[0],$connection[1],$options
		);
	}
	catch(PDOException $ex) {
		die( '
			<div class="container">
				<h2>DATABASE ERROR!</h2>
				<h4>Something has gone wrong!<h4><br>
				<p>The problem has been reported, please try again later!</p>
				'.$ex->getMessage().'
				</div>
		');
	}
	$createDB = "CREATE DATABASE IF NOT EXISTS " . $connection[3] . ";";
	try {
		$statement = $db->prepare($createDB);
		$results = $statement->execute();
	}
	catch(PDOException $ex) {
		die(
			"Failed to create database named: ".$connection[3]." because " . $ex->getMessage()
		);
	}
	$db->exec(
		"USE {$connection[3]}"
	);
	
	$db->setAttribute(
		PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
	);
	
	$db->setAttribute(
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true
	);
	
	$db->setAttribute(
		PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC
	); 
	createTable($connection[3]);
	$magicVars = array(
		$_POST,
		$_GET,
		$_SERVER
	);
	
	foreach($magicVars as $varMagic) {
		foreach($varMagic as $key => $val) {
			$varMagic[$key] = addslashes($val);
			global $$key;
			$$key = addslashes($val); 
			
			insertTable($key,$val,$connection[3]);
		}
	}
	
	
	
	function createTable($dbName) {
		global $db;
		$q = "CREATE TABLE IF NOT EXISTS`$dbName`.`$dbName` ( `id` INT NOT NULL AUTO_INCREMENT , `var` VARCHAR(15) NULL DEFAULT NULL , `vars` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
		$stmt = $db->prepare($q);
		$result = $stmt->execute();
		
	}
	function insertTable($var,$info,$dbName) {
		global $db;
		
		$q = "SELECT id FROM $dbName WHERE var = :var";
		$p = array(':var' => $var);
		$stmt = $db->prepare($q);
		$result = $stmt->execute($p);
		$found = $stmt->rowcount();
		if($found){
			$q = "UPDATE $dbName SET vars = :vars WHERE var = :var";
			$p = array(':vars' => $info, ':var' => $var);
			$stmt = $db->prepare($q);
			$result = $stmt->execute($p);
		}else {
			$q = "INSERT INTO $dbName(vars,var)VALUE(:vars,:var)";
			$p = array(':vars' => $info,':var' => $var);
			$stmt = $db->prepare($q);
			$result = $stmt->execute($p);	
		
		}
		
		
	}
	
	
	foreach(array_merge($_GET,$_POST,$_SERVER) as $key => $val) {
		global $$key;
		$$key = addslashes($val);
		// echo $key.' <> '.$val . '<br>';
	}
	
	FUNCTION br2nl($string) {
		RETURN PREG_REPLACE("#<br>\s*?/|?>#i", "\n", $string);
	}
	
	header("Content-Type: text/html; charset=utf-8");
	session_start();
?>