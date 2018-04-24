<?php

$db_conx = mysqli_connect("localhost", "id232933_root", "rootpass", "id232933_watchnami");

//evaluate the connection_aborted
if (mysqli_connect_errno()) {
	echo mysql_connect_error();
	exit();
	
} 
?>