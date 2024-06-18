<?php
if($_SERVER["REQUEST_METHOD"]=="POST") {
	include "exporter.php";
	include "orm-db.php";
	$session_key=$_POST["session_key"];
	session_id($session_key);
	session_start();
	unset($_SESSION["id"]);
	unset($_SESSION["user"]);
	unset($_SESSION["user_level"]);
	unset($_SESSION["role"]);
	session_destroy();
}
?>
