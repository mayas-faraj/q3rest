<?php
if($_SERVER["REQUEST_METHOD"]=="POST") {
	include "orm-db.php";

	if(isset($_COOKIE["PHPSESSID"]))
		session_id($_COOKIE["PHPSESSID"]);

	session_start();

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	$ordDb=new OrmDb();
	$data=json_decode(file_get_contents("php://input"));
	if(isset($data->user)) {
		echo json_encode($ordDb->login($data->user, $data->password));
	}
	else {
		$ordDb->logout();
	}
}
?>
