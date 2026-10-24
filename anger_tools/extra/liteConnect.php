<?php

    //PHP LITE CONNECTION FILE
    
    $dbFileName = 'pma.db';
    $dbFile = __DIR__ . "/pma/database/" . $dbFileName;
    
    if(file_exists($dbFile)) {
        try {
            $db = new PDO("sqlite:" . $dbFile);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    } else {
        // $file = $dbFileName;
        // $folder = $dbFile;
        // $fh = fopen($folder,'a');
        // fwrite($fh, '/* Database Created for '.$file . ' */');
        // fclose($fh);
        // header("Location: extra/");
        // exit("Database Created");
        
        TRIGGER_ERROR("ERROR: FILE NOT FOUND", E_USER_WARNING);
        echo '<a href="pma/" target="_top">Create Database File</a>';
        exit;
    }
    
    FUNCTION br2nl($string) {
        RETURN PREG_REPLACE("#<br\s*?/?>#i", "\n", $string);
    }
    
    header("Content-Type: text/html; charset=utf-8");
	date_default_timezone_set('America/Denver');
    session_start();


?>