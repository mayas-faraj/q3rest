<?php
ini_set("show_errors", 0);
ini_set("show_startup_errors", 0);
error_reporting(0);

define("GET_SITEURL", "http://localhost/");
define("DB_NAME", "db");
define("DB_USER", "admin");
define("DB_PASSWORD", "YOURPASSWORD");
define("DB_HOST", "localhost");
if(!defined("ABS_PATH"))
	define("ABS_PATH", __DIR__ . "/");

define("ADMIN_USER_LEVEL", 1);
define("MANAGER_USER_LEVEL", 2);
define("LOGISTIC_USER_LEVEL", 3);
define("CLIENT_USER1_LEVEL", 4);
define("CLIENT_USER2_LEVEL", 5);
define("CLIENT_USER3_LEVEL", 6);
define("ONPROCESS_STATUS", 1);
define("ONHOLD_STATUS", 2);
define("CANCEL_STATUS", 4);
define("ONDELIVERY_STATUS", 5);
define("ONSHOPPING_STATUS", 6);
define("SHIPMETHOD_SHIP", 1);
define("SHIPMETHOD_DELIVER", 2);
?>
