<?php
echo ++$argv[1];
$_ = $_SERVER['_'];

register_shutdown_function(function () {
  #global $_, $argv;
  #pcntl_exec($_, $argv);
});

//Import settings and begin connect to mysql tables for the system

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
