<?php

$sqdb=array();
$sqdb["tabe_redirect"]=array();

//####################################################################################################################
//####################################################################################################################-- Connection To Database
//####################################################################################################################

$sqdb["index"]=array();
$sqdb["index"]["db_server"]=$sqdbsettings["table_index_server"];
$sqdb["index"]["db_username"]=$sqdbsettings["table_index_username"];
$sqdb["index"]["db_password"]=$sqdbsettings["table_index_password"];
$sqdb["index"]["db_database"]=$sqdbsettings["table_index_database"];

$sqdb["save"]=array();
$sqdb["save"]["db_server"]=$sqdbsettings["table_save_server"];
$sqdb["save"]["db_username"]=$sqdbsettings["table_save_username"];
$sqdb["save"]["db_password"]=$sqdbsettings["table_save_password"];
$sqdb["save"]["db_database"]=$sqdbsettings["table_save_database"];

$connections=array();

function sqdb_connect_table($table){
	global $connections;
	global $sqdb;
	if (!isset($connections["".$table.""])){
		$connections["".$table.""] = new mysqli($sqdb["".$table.""]["db_server"], $sqdb["".$table.""]["db_username"], $sqdb["".$table.""]["db_password"], $sqdb["".$table.""]["db_database"]);
		if ($connections["".$table.""]->connect_error) {
			log_write("We are unable to conect to our backend systems. Try again in a few minutes, we may be under heavy load: ".$sqdb["".$table.""]["db_server"].", ".$sqdb["".$table.""]["db_username"].", ".$sqdb["".$table.""]["db_password"].", ".$sqdb["".$table.""]["db_database"]."","error");
		}
	}
}

//####################################################################################################################
//####################################################################################################################-- SQBD -> Query
//####################################################################################################################

function sqdb_query($query,$table="default"){
	global $connections;
	if (!isset($connections["".$table.""])){ sqdb_connect_table($table); }
	$return=false;

	$return = $connections["".$table.""]->query($query);
	return $return;
}

//####################################################################################################################
//####################################################################################################################-- SQBD -> Num -> Rows
//####################################################################################################################

function sqdb_num_rows($query,$table="default"){
	global $connections;
	if (!isset($connections["".$table.""])){ sqdb_connect_table($table); }
	return $query->num_rows;
}

//####################################################################################################################
//####################################################################################################################-- SQBD -> Fetch -> Array
//####################################################################################################################

function sqdb_fetch_array($query,$table="default"){
	global $connections;
	if (!isset($connections["".$table.""])){ sqdb_connect_table($table); }
	return $query->fetch_assoc();
}

//####################################################################################################################
//####################################################################################################################-- SQBD -> Insert -> ID
//####################################################################################################################

function sqdb_insert_id($table="default"){
	global $connections;
	if (!isset($connections["".$table.""])){ sqdb_connect_table($table); }
	return $connections["".$table.""]->insert_id;
}

//####################################################################################################################
//####################################################################################################################-- SQBD -> Close
//####################################################################################################################

function sqdb_close($table="default"){
	global $connections;
	if (!isset($connections["".$table.""])){ sqdb_connect_table($table); }
	$connections["".$table.""]->close();
}

?>
