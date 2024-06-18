<?php
class Exporter {
	static function export($name, $value) {
		if(count($value)>0) {
			$file=fopen("documents/".$name, "w");
			fputcsv($file, array_keys($value[0]), ";");
			foreach($value as $row) 
				fputcsv($file, $row, ";");
			fclose($file);
			return GET_SITEURL . "backend/downloads.php?filename=$name";
		}
		else
			return false;
	}
}
?>
