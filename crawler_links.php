<?php

include("functions.php");
include("startup.php");
include("sqdb.php");

log_clear("links");
log_write("Startup Test @ ".make_timestamp()." with process count ".$argv."!","links");


log_write("Loading links to check...","links");


?>
