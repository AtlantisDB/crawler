<?php

//Import settings and begin connect to mysql tables for the system

register_shutdown_function('handleFatalPhpError');

function handleFatalPhpError() {
   $last_error = error_get_last();
   if($last_error['type'] === E_ERROR) {
     $error=json_encode($last_error,true);
      error_log("\n$error", 3, "/var/master/output_error.txt");
   }
}


$sqdbsettings=array();

$import = file_get_contents("/var/master/mysql.json");
$json_a = json_decode($import, true);
if ($json_a === null) {
  die("Unable to decode and load data");
}

$sqdbsettings["table_index_server"]=$json_a["index"]["server"];
$sqdbsettings["table_index_username"]=$json_a["index"]["username"];
$sqdbsettings["table_index_password"]=$json_a["index"]["password"];
$sqdbsettings["table_index_database"]=$json_a["index"]["database"];

$sqdbsettings["table_save_server"]=$json_a["save"]["server"];
$sqdbsettings["table_save_username"]=$json_a["save"]["username"];
$sqdbsettings["table_save_password"]=$json_a["save"]["password"];
$sqdbsettings["table_save_database"]=$json_a["save"]["database"];

?>
