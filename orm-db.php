<?php
require __DIR__ . '../../vendor/autoload.php';
use Firebase\JWT\JWT;

include "orm-config.php";
include "log4php/Logger.php";

class OrmDb {
	public function __construct() {
		Logger::configure("log4php.xml");
		$this->query_logger=Logger::getLogger("queryLogger");
		$this->exception_logger=Logger::getLogger("exceptionLogger");
		$this->access_logger=Logger::getLogger("accessLogger");
		$this->mysqli=new MySqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $this->mysqli->set_charset('utf8');
		$this->mysqli->query("set time_zone = '+03:00'");
		if($this->mysqli->connect_error!=NULL)
			die("Error connect to database: " . $this->mysqli->connect_error);
	}

	public function create($table, $cols) {
		$count=count($cols);
		if($count>0) {
			$str_cols="";
			$str_vals="";

			$i=0;

			foreach($cols as $key=>$val) {
				$str_cols.=$key;
				if($val===NULL)
					$str_vals.="NULL";
				else if(is_string($val))
					$str_vals.="'" . trim($val) . "'";
				else
					$str_vals.=$val;
				if($i++<$count-1) {
					$str_cols.=", ";
					$str_vals.=", ";
				}
			}


			$sql="INSERT INTO $table ($str_cols) VALUES ($str_vals);";
			$this->query_logger->trace($sql);
			$this->mysqli->query($sql);
			if($this->mysqli->error==NULL)
				return $this->success_array($this->mysqli->insert_id);
			else
				return $this->error_array($this->mysqli->error, "insert error");
		}
		else
			return $this->no_values_array();
	}
	
	public function create_query($table, $cols, $source_table, $source_cols) {
		if(count($cols) == count($source_cols) && count($cols)>0) {
			$str_cols=implode(',', $cols);
			$str_source_cols=implode(',' ,$source_cols);

			$sql="INSERT INTO $table ($str_cols) SELECT $str_source_cols FROM $source_table;";
			$this->query_logger->trace($sql);
			$this->mysqli->query($sql);
			if($this->mysqli->error==NULL)
				return $this->success_array($this->mysqli->insert_id);
			else
				return $this->error_array($this->mysqli->error, "insert error");
		}
		else
			return $this->no_values_array();
	}

	public function read_id_by_user($table, $user) {
		return $this->read_scalar($table, "id", array("user"=>$user), TRUE);
	}

	public function read_id_by_name($table, $name) {
		return $this->read_scalar($table, "id", array("name"=>$name), TRUE);
	}

	public function read_id_by_title($table, $title) {
		return $this->read_scalar($table, "id", array("title"=>$title), TRUE);
	}

	public function read_scalar($table, $col, $conditions=NULL, $is_int=FALSE) {
		$where=$this->get_conditions($conditions);
		$sql="SELECT $col FROM $table $where";
		$this->query_logger->trace($sql);
		$result=$this->mysqli->query($sql);
		if($this->mysqli->error==NULL) {
			if($row=$result->fetch_array()) {
				$value = $row[0];
				if($is_int) $value=intval($value);

				if($value!=NULL) 
					return $this->success_array($value);
				else 
					return $this->success_array(0, "no value");
			}
			else {
				if($is_int)
					return $this->success_array(0, "no rows");
				else
					return $this->success_array(NULL, "no rows");
			}
		}
		else
			return $this->error_array($this->mysqli->error, "read error");
	}

	public function read($table, $cols, $conditions=NULL, $length=NULL, $offset=NULL) {
		$where=$this->get_conditions($conditions);
		$limit="";
		$str_cols="";
		$count=count($cols);
		$i=0;

		if($length!=NULL)
			$limit="LIMIT $length";

		if($offset!=NULL)
			$limit.=" OFFSET $offset";


		if(array_values($cols)!==$cols)
			$cols=array_keys($cols);

		foreach($cols as $col) {
			$str_cols.=$col;
			if($i++<$count-1) 
				$str_cols.=", ";
		}

		$sql="SELECT $str_cols FROM $table $where $limit;";
		$this->query_logger->trace($sql);
		$result=$this->mysqli->query($sql);
		if($this->mysqli->error==NULL) {
			$rows=array();
			while($row=$result->fetch_assoc())
				$rows[]=$row;

			return $rows;
		}
		else
			return $this->error_array($this->mysqli->error, "read error");
	}

	public function update($table, $cols, $conditions) {
		$count=count($cols);
		if($count>0) {
			$where=$this->get_conditions($conditions);
			$i=0;
			$str_cols="";

			foreach($cols as $key=>$val) {
				$str_cols.=$key . "=";
				if($val===NULL)
					$str_cols.="NULL";
				else if(is_string($val) && !preg_match("/`\w+`/", $val))
					$str_cols.="'".trim($val) ."'";
				else
					$str_cols.="$val";

				if($i++<$count-1)
					$str_cols.=", ";
			}

			$sql="UPDATE $table SET $str_cols $where";
			$this->query_logger->trace($sql);
			$this->mysqli->query($sql);
			if($this->mysqli->error==NULL)
				return $this->success_array();
			else
				return $this->error_array($this->mysqli->error, "update error");
		}
		else
			return $this->no_values_array();
	}

	public function drop($table, $conditions) {
		$where=$this->get_conditions($conditions);
		$sql="DELETE FROM $table $where";
		$this->query_logger->trace($sql);
		$this->mysqli->query($sql);
		if($this->mysqli->error==NULL)
			return $this->success_array();
		else
			return $this->error_array($this->mysqli->error, "drop error");
	}

	public function login($user, $password) {
		$user_info=$this->read("login_view", array("id" ,"user", "code", "role", "user_level", "is_disabled", "is_confirmed"), array("user"=>$user));
		if(count($user_info)>0)
			if(password_verify($password, $user_info[0]["code"])) 
				if($user_info[0]["is_disabled"]=="0")  
					if($user_info[0]["is_confirmed"]=="1") { 
						$session_key=session_id();
						$this->update("users", array("session_key"=>$session_key), array("user"=>$user)); 
						$_SESSION["id"]=$user_info[0]["id"];
						$_SESSION["user"]=$user_info[0]["user"];
						$_SESSION["user_level"]=$user_info[0]["user_level"];
						$_SESSION["role"]=$user_info[0]["role"];

						if($_SESSION["user_level"]>=7 && $_SESSION["user_level"]<=11) {
							$key = 'master123$';
							$payload = [
								'nam' => $user_info[0]["user"],
								'lvl' => $user_info[0]["user_level"],
								'rol' => $user_info[0]["role"]
							];
							
							$jwt = JWT::encode($payload, $key, 'HS256');
							return $this->success_array($jwt, $_SESSION["user_level"]);
						}
						else
							return $this->success_array($session_key, $_SESSION["user_level"]);
					}
					else
						return $this->failed_array($user, "user is not activated");
				else 
					return $this->failed_array($user, "user is disabled");
			else
				return $this->failed_array($password, "password error");
		else
			return $this->failed_array($user, "user not exists");
	}

	public function logout() {
		$this->update("users", array("session_key"=>NULL), array("user"=>$_SESSION["user"])); 
		unset($_SESSION["id"]);
		unset($_SESSION["user"]);
		unset($_SESSION["role"]);
		unset($_SESSION["user_level"]);
		session_destroy();
		return $this->success_array("", "logout successed");
	}



	public function __destruct() {
		$this->mysqli->close();
		unset($this->mysqli);
	}

	private function no_values_array() {
		return array("status"=>"error", "msg"=>"no values to insert or update");
	}
	
	private function error_array($value=NULL, $msg=NULL) {
		$this->exception_logger->error("$msg ($value)");
		$arr=array("status"=>"error");
		if(isset($value)) $arr["value"]=$value; else $arr["value"]="";
		if(isset($msg)) $arr["msg"]=$msg; else $arr["msg"]="";
		return $arr;
	}

	private function failed_array($value=NULL, $msg=NULL) {
		$this->access_logger->warn("$msg ($value)");
		$arr=array("status"=>"failed");
		if(isset($value)) $arr["value"]=$value; else $arr["value"]="";
		if(isset($msg)) $arr["msg"]=$msg; else $arr["msg"]="";
		return $arr;
	}

	private function success_array($value=NULL, $msg=NULL) {
		$arr=array("status"=>"success");
		if(isset($value)) $arr["value"]=$value; else $arr["value"]="";
		if(isset($msg)) $arr["msg"]=$msg; else $arr["msg"]="";
		return $arr;
	}

	private function get_conditions($conditions) {
		$conditions_string=$this->get_inner_conditions($conditions);
		if($conditions_string!="")
			return "WHERE " . $conditions_string;
		else
			return "";
	}

	private function get_inner_conditions($conditions, $operation_or=TRUE) {
		if($conditions!=NULL && count($conditions)>0) {
			$conditions_string="";
			$first=TRUE;
			$count=count($conditions);
			foreach($conditions as $key=>$value) {
				if(!$first) 
					if($operation_or)
						$conditions_string.=" OR ";
					else
						$conditions_string.=" AND ";
				else
					$first=FALSE;

				if(!is_array($value)) 
					$conditions_string.=$this->get_condition($key, $value);
				else 
					$conditions_string.="(".$this->get_inner_conditions($value, !$operation_or).")";
			}

			if($conditions_string=="()")
				return "";
			else
				return $conditions_string;
		}
		else
			return "";
	}

	private function get_condition($key, $value) {
		$condition="";

		if(strpos(strtolower($key), " like"))
			if($value==NULL)
				$condition.="$key '%'";
			else
				$condition.="$key '%$value%'";
		elseif(is_bool($value) && $value)
			$condition.="$key IS TRUE";
		elseif(is_bool($value) && !$value)
			$condition.="$key IS FALSE";
		elseif($value===NULL || $value==="NULL") 
			$condition.="$key IS NULL";
		elseif(is_string($value))
			$condition.="$key='$value'";
		else
			$condition.="$key=$value";

		return $condition;
	}

	private $query_logger;
	private $exception_logger;
	private $access_logger;
	private $mysqli;
}
