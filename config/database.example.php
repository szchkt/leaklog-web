<?php

define('DB_NAME', 'leaklog');
define('DB_USER', 'leaklog');
define('DB_PASSWD', 'leaklog');

$dbconn = pg_pconnect('host=localhost port=5432 dbname='.DB_NAME.' user='.DB_USER.' password='.DB_PASSWD)
	or die('Could not connect to database: ' . pg_last_error());

?>
