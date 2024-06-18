<?php
include "orm-config.php";
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream'); 
header('Content-Disposition: attachment; filename='. $_GET["filename"]);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
echo "\xEF\xBB\xBF"; // UTF-8 BOM
echo file_get_contents("/var/www/html/backend/documents/" . $_GET["filename"]);
?>
