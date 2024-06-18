<?php
//if($_SERVER["REQUEST_METHOD"]=="POST") {
if(true) {
	include "exporter.php";
	include "orm-db.php";

	$ordDb=new OrmDb();
	$data=json_decode(file_get_contents("php://input"));
	$operation=$data->operation;

	if(isset($_COOKIE["PHPSESSID"])) 
		session_id($_COOKIE["PHPSESSID"]);
	elseif(isset($data->session_id))  
		session_id($data->session_id);


	session_start();

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');
	if(!(isset($_SESSION["user_level"]) && ($_SESSION["user_level"]==ADMIN_USER_LEVEL || ($_SESSION["user_level"]==LOGISTIC_USER_LEVEL)))) {
		output_limit_access();
		die();
	}
	if(isset($_FILES["img_file"]) && !empty($_FILES["img_file"]["tmp_name"])) {
		$check=getimagesize($_FILES["img_file"]["tmp_name"]);

		if($check!==FALSE) {
			$img_file_dir=ABS_PATH . "images/stores/";
			$i=0;
			$filename=NULL;
			$target_path=NULL;
			do {
				$ext=pathinfo($_FILES["img_file"]["name"], PATHINFO_EXTENSION);
				$filename= basename($_FILES["img_file"]["name"], ".".$ext);
				if($i==0)
					$filename.=".$ext";
				else
					$filename.="_$i.$ext";
				
				$target_path=$img_file_dir . $filename;
				$i++;
			} while(file_exists($target_path));

			if(move_uploaded_file($_FILES["img_file"]["tmp_name"], $target_path)) 
				output(array("status"=>"success", "msg"=> GET_SITEURL."backend/images/stores/$filename"));
			else
				output(array("status"=>"error", "msg"=> "can't upload img to ".$target_path."," . $_FILES["img_file"]["error"]));
		}
		else {
			output(array("status"=>"error", "msg"=>"uploaded file is not an image"));
		}

		die();
	}
	switch($operation) {
	case "read-statistics":
		output($ordDb->read("statistics_view", array("messages_count", "unread_messages_count", "products_count", "stores_count", "clients_count", "orders_count", "completed_orders_count", "sum_ordered_products", "ads_count", "ads_products_count", "ads_categories_count", "ads_pages_count", "ads_stores_count")));
		break;
	case "read-roles":
		output($ordDb->read("roles", array("name", "description"), NULL));
		break;
	case "read-provinces":
		output($ordDb->read("provinces_view", array("name"), NULL));
		break;
	case "read-users-admin-logistic":
		output($ordDb->read("login_view", array("user", "role", "is_disabled"), array(array("user_level"=>ADMIN_USER_LEVEL), array("user_level"=>LOGISTIC_USER_LEVEL), array("user_level"=>7), array("user_level"=>8), array("user_level"=>9), array("user_level"=>10), array("user_level"=>11))));
		break;
	case "read-users-manager-count":
		output($ordDb->read_scalar("login_view", "count(id)", array("user_level"=>MANAGER_USER_LEVEL)));
		break;
	case "read-users-manager":
		$condition=array("user_level"=>MANAGER_USER_LEVEL);

		$length=$data->length??NULL;
		$offset=$data->offset??NULL;

		if(isset($data->is_disabled) && $data->is_disabled)
			$condition["is_disabled"]=1;

		if(isset($data->search) && $data->search!="")
			$condition[]=array("name like"=>$data->search, "user like"=>$data->search, "phone like"=>$data->search);

		output($ordDb->read("users_view", array("user", "name", "phone", "address", "province", "is_disabled", "store"), array($condition), $length, $offset));
		break;
	case "read-users-client-count":
		output($ordDb->read_scalar("login_view", "count(id)", array(array(array("user_level"=>CLIENT_USER1_LEVEL), array("user_level"=>CLIENT_USER2_LEVEL), array("user_level"=>CLIENT_USER3_LEVEL)))));
		break;
	case "read-users-client":
		$condition=array(array(array("user_level"=>CLIENT_USER1_LEVEL), array("user_level"=>CLIENT_USER2_LEVEL), array("user_level"=>CLIENT_USER3_LEVEL)));

		$length=$data->length??NULL;
		$offset=$data->offset??NULL;
		
		if(isset($data->is_disabled) && $data->is_disabled)
			$condition["is_disabled"]=1;

		if(isset($data->search) && $data->search!="")
			$condition[]=array("name like"=>$data->search, "user like"=>$data->search, "phone like"=>$data->search);

		$length=$data->length??NULL;
		$offset=$data->offset??NULL;

		if(!$data->export)
			output($ordDb->read("users_view", array("user", "name", "phone", "address", "province", "user_level", "role", "is_disabled"), array($condition), $length, $offset));
		else
			export("المستخدمون.csv", $ordDb->read("users_view", array("user AS 'المستخدم'", "name AS 'الاسم'", "phone AS 'الهاتف'", "address AS 'العنوان'", "province AS 'المحافظة'", "is_disabled AS 'معطل'"), array($condition)));
		break;
	case "create-user-admin":
		$user=$data->user;
		$password=$data->password;
		$comment=$data->comment??NULL;

		$role_result=$ordDb->read_scalar("roles", "id", array("user_level"=>ADMIN_USER_LEVEL), TRUE);
		output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>$role_result["value"], "name"=>$user, "is_confirmed"=>1)));
		break;
	case "create-user-logistic":
		$user=$data->user;
		$password=$data->password;
		$comment=$data->comment??NULL;

		$role_result=$ordDb->read_scalar("roles", "id", array("user_level"=>LOGISTIC_USER_LEVEL), TRUE);
		output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>$role_result["value"], "name"=>$user, "is_confirmed"=>1)));
		break;
	case "create-user-warehouse1":
		$user=$data->user;
		$password=$data->password;
		$comment=$data->comment??NULL;
		output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>7, "name"=>$user, "is_confirmed"=>1)));
		break;
	case "create-user-warehouse2":
		$user=$data->user;
		$password=$data->password;
		$comment=$data->comment??NULL;
		output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>8, "name"=>$user, "is_confirmed"=>1)));
		break;
	case "create-user-warehouse3":
		$user=$data->user;
		$password=$data->password;
		$comment=$data->comment??NULL;
		output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>9, "name"=>$user, "is_confirmed"=>1)));
		break;
	case "create-user-warehouse4":
			$user=$data->user;
			$password=$data->password;
			$comment=$data->comment??NULL;
			output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>10, "name"=>$user, "is_confirmed"=>1)));
			break;
	case "create-user-warehouse5":
			$user=$data->user;
			$password=$data->password;
			$comment=$data->comment??NULL;
			output($ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>11, "name"=>$user, "is_confirmed"=>1)));
			break;
	case "create-user-manager":
		if(isset($data->user))
			if(isset($data->password))
				if(isset($data->name)) {
					$phone=$data->phone??NULL;
					$province=$data->province??NULL;
					$address=$data->address??NULL;

					$role_result=$ordDb->read_scalar("roles", "id", array("user_level"=>MANAGER_USER_LEVEL), TRUE);
					$province_id=NULL;
					if($province!=NULL) {
						$province_result=$ordDb->read_id_by_name("provinces", $province);
						if($province_result["status"]=="success")
							$province_id=$province_result["value"];
					}

					if($province==NULL || $province_id!=NULL) {
						$user_result=$ordDb->create("users", array("user"=>$data->user, "code"=>password_hash($data->password, PASSWORD_DEFAULT), "role_id"=>$role_result["value"], "name"=>$data->name, "is_confirmed"=>1));
						if($user_result["status"]=="success")
							output($ordDb->create("userinfo", array("user_id"=>$user_result["value"], "phone"=>$phone, "province_id"=>$province_id, "address"=>$address)));
						else
							output_invalid("duplicate, ".$user_result["msg"]);
					}
					else
						output_invalid($province);
				}
				else
					output_missed("name");
			else
				output_missed("password");
		else
			output_missed("user");
		break;
	case "update-user-name":
		$user=$data->user;
		$name=$data->name;
		output($ordDb->update("users", array("name"=>$name), array("user"=>$user)));
		break;
	case "update-user-level":
			$user=$data->user;
			$level=$data->user_level;
			output($ordDb->update("users", array("role_id"=>$level), array("user"=>$user)));
		break;
	case "update-user-password":
		$user=$data->user;	
		$password=$data->password;
		output($ordDb->update("users", array("code"=>password_hash($password, PASSWORD_DEFAULT)), array("user"=>$user)));
		break;

	case "update-user-disabled":
		$user=$data->user;
		$is_disabled=$data->is_disabled;
		if($is_disabled=="1") {
			$session_key=$ordDb->read_scalar("users", "session_key", array("user"=>$user))["value"];
			if($session_key)
				operation_destroy_session($session_key);
		}
		
		output($ordDb->update("users", array("is_disabled"=>$is_disabled), array("user"=>$user)));
		break;
	case "update-user-admin":
		if(isset($data->user)) {
			$name=$data->name??NULL;
			if(isset($data->new_user))
				$new_user=$data->new_user;
			else
				$new_user=$data->user;

			output($ordDb->update("users", array("user"=>$new_user, "name"=>$name), array("user"=>$user)));
		}
		else
			output_missed("user");
		break;
	case "update-user-manager":
		$user=$data->user;
		$new_user=$data->new_user??$user;
		$phone=$data->phone??NULL;
		$province=$data->province??NULL;
		$address=$data->address??NULL;
		$name=$data->name??"";

		$province_id=NULL;
		if($province!=NULL) {
			$province_result=$ordDb->read_id_by_name("provinces", $province);
			if($province_result["status"]=="success")
				$province_id=$province_result["value"];
		}

		if($province==NULL || $province_id!=NULL) {
			$ordDb->update("users", array("user"=>$new_user, "name"=>$name), array("user"=>$user));
			$user_id=$ordDb->read_id_by_user("users", $user)["value"];
			output($ordDb->update("userinfo", array("phone"=>$phone, "province_id"=>$province_id, "address"=>$address), array("user_id"=>$user_id))); 
		}
		else
			output_invalid($province);


		break;
	case "drop-user":
		if(isset($data->user)) {
			operation_destroy_session($session_key);
			output($ordDb->drop("users", array("user"=>$data->user)));
		}
		else
			output_missed("user");
		break;
	case "read-support-messages-count":
		output($ordDb->read_scalar("support_messages", "count(id)"));
		break;
	case "read-support-messages":
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;
		output($ordDb->read("support_messages_view", array("id", "user", "title", "message", "sent_at", "is_unread"), NULL, $length, $offset));
		break;
	case "update-support-message":
		$id=$data->id??NULL;
		if($id!=NULL)
			output($ordDb->update("support_messages", array("is_unread"=>0), array("id"=>$id)));
		else
			output_missed("[id]");
		break;
	case "drop-support-message":
		$message_id=$data->message_id;
		output($ordDb->drop("support_messages", array("id"=>$message_id)));
		break;
	case "create-category":
		if(isset($data->name))
			output($ordDb->create("categories", array("name"=>$data->name)));
		else
			output_missed("name");
		break;
	case "update-category":
		if(isset($data->name))
			if(isset($data->new_name))
				output($ordDb->update("categories", array("name"=>$data->new_name), array("name"=>$data->name)));
			else
				output_missed("new_name");
		else
			output_missed("name");
		break;
	case "drop-category":
		if(isset($data->name))
			output($ordDb->drop("categories", array("name"=>$data->name)));
		else
			output_missed("name");
		break;
	case "read-categories":
		if(!$data->export)
			output($ordDb->read("categories", array("name", "created_at")));
		else
			export("التصنيفات.csv", $ordDb->read("categories", array("name AS 'الاسم'", "created_at AS 'اضيف بتاريخ'")));
		break;
	case "create-subcategory":
		if(isset($data->name))
			//if(isset($data->singular_name))
				if(isset($data->category)) {
					$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
					output($ordDb->create("subcategories", array("name"=>$data->name, "singular_name"=>$data->singular_name, "category_id"=>$category_id)));
				}
				else
					output_missed("category");
			//else
				//output_missed("singular_name");
		else
			output_missed("name");
		break;
	case "update-subcategory":
		if(isset($data->name))
			if(isset($data->new_name))
				//if(isset($data->singular_name))
					if(isset($data->category)) {
						$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
						output($ordDb->update("subcategories", array("name"=>$data->new_name, "singular_name"=>$data->singular_name), array(array("name"=>$data->name, "category_id"=> $category_id))));
					}
					else
						output_missed("category");
				//else
				//	output_missed("singular_name");
			else
				output_missed("new_name");
		else
			output_missed("name");
		break;
	case "drop-subcategory":
		if(isset($data->name))
			if(isset($data->category)) { 
				$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
				output($ordDb->drop("subcategories", array(array("name"=>$data->name, "category_id"=>$category_id))));
			}
			else
				output_missed("category");
		else
			output_missed("name");
		break;
	case "read-subcategories":
		$condition=null;
		if(isset($data->category)) {
			$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
			$condition=array("category_id"=>$category_id);
		}
		if(!$data->export)
			output($ordDb->read("subcategories", array("name", /*"singular_name",*/ "created_at"), $condition));
		else
			export("التصنيفات الفرعية.csv", $ordDb->read("subcategories", array("name AS 'الاسم'", "created_at AS 'اضيف بتاريخ'"), $condition));
		break;
	case "read-subcategories-of-store":
		if(isset($data->store))	
			output($ordDb->read("store_subcategories_view", array("category", "subcategory"), array(array("store"=>$data->store))));
		else
			output_missed("store");
		break;

	case "read-sizes":
		if(isset($data->category)) 
			if(isset($data->subcategory)) {
				$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
				$subcategory_id=$ordDb->read_scalar("subcategories", "id", array(array("name"=>$data->subcategory, "category_id"=>$category_id)), TRUE)["value"];
			output($ordDb->read("sizes", array("name"), array("subcategory_id"=>$subcategory_id)));
			}
			else
				output_missed("subcategory");
		else
			output_missed("category");
		break;
	case "create-size":
		if(isset($data->name))
			if(isset($data->category)) 
				if(isset($data->subcategory)) {
					$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
					$subcategory_id=$ordDb->read_scalar("subcategories", "id", array(array("name"=>$data->subcategory, "category_id"=>$category_id)), TRUE)["value"];
					output($ordDb->create("sizes", array("name"=>$data->name, "subcategory_id"=>$subcategory_id)));
				}
				else
					output_missed("subcategory");
			else
				output_missed("category");
		else
			output_missed("name");
		break;
	case "update-size":
		if(isset($data->name))
			if(isset($data->new_name))
				if(isset($data->category)) 
					if(isset($data->subcategory)) {
						$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
						$subcategory_id=$ordDb->read_scalar("subcategories", "id", array(array("name"=>$data->subcategory, "category_id"=>$category_id)), TRUE)["value"];
						output($ordDb->update("sizes", array("name"=>$data->new_name), array("name"=>$data->name, "subcategory_id"=>$subcategory_id)));
					}
					else
						output_missed("subcategory");
				else
					output_missed("category");
			else
				output_missed("new_name");
		else
			output_missed("name");
		break;
	case "drop-size":
		if(isset($data->name))
			if(isset($data->category)) 
					if(isset($data->subcategory)) {
						$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
						$subcategory_id=$ordDb->read_scalar("subcategories", "id", array(array("name"=>$data->subcategory, "category_id"=>$category_id)), TRUE)["value"];
						output($ordDb->drop("sizes", array("name"=>$data->name, "subcategory_id"=>$subcaegory_id)));
					}
					else
						output_missed("subcategory");
			else
				output_missed("category");
		else
			output_missed("name");
		break;
	case "read-stores-count":
	    $conditions=NULL;
		if(isset($data->search) && $data->search!="")
			$conditions=array("name like"=>$data->search, "comment like"=>$data->search, "address like"=>$data->search);
		if(isset($data->province) && $data->province!="")
			if($conditions)
			    $conditions=array(array($condition, array("province"=>$data->province)));
			else
			    $conditions=array("province"=>$data->province);

		output($ordDb->read_scalar("stores_view", "count(id)", $conditions));
		break;
	case "read-stores":
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;	
		$conditions=NULL;
		if(isset($data->search) && $data->search!="")
			$conditions=array("name like"=>$data->search, "comment like"=>$data->search, "address like"=>$data->search);
		if(isset($data->province) && $data->province!="")
			if($conditions)
			    $conditions=array(array($condition, array("province"=>$data->province)));
			else
			    $conditions=array("province"=>$data->province);


		if(!$data->export)
			output($ordDb->read("stores_view", array("id", "name", "logo", "province", "address", "user", "comment", "created_at", "updated_at", "price_round", "is_disabled", "is_city_enabled"), $conditions, $length, $offset));
		else
			export("المتاجر.csv", $ordDb->read("stores_view", array("name AS 'الاسم'", "address AS 'العنوان'", "user AS 'المدير'", "comment AS 'ملاحظات'", "created_at AS 'تاريخ الاضافة'", "updated_at AS 'تاريخ التعديل'", "price_round AS 'تقريب العملة'", "is_disabled AS 'معطل'")));
		break;
	case "read-store":
		if(isset($data->name))
			output($ordDb->read("stores_view", array("id", "name", "logo", "province", "address", "user", "comment", "created_at", "updated_at", "price_round", "is_disabled"), array("name"=>$data->name)));
		else
			output_missed("name");
		break;
	case "create-store":
		if(isset($data->name))
	       		if(isset($data->logo)) 
				if(isset($data->user)) 
					if(isset($data->province)) {
						$address=$data->address??NULL;
						$comment=$data->comment??NULL;
						$price_round=$data->price_round??NULL;
						$user_id=$ordDb->read_id_by_user("users", $data->user); 
						$province_id=$ordDb->read_id_by_name("provinces", $data->province)["value"]; 
						if($user_id!=NULL) {
							output($ordDb->create("stores", array("name"=>$data->name, "logo"=>$data->logo, "province_id"=>$province_id, "address"=>$address, "user_id"=>$user_id["value"], "comment"=>$comment, "price_round"=>$price_round)));
						}
						else
							output_missed($data->user);
				}
				else
					output_missed("user");
			else
				output_missed("logo");
		else
			output_missed("name");
		break;
	case "update-store":
		if(isset($data->name))
			if(isset($data->logo))
		       		if(isset($data->province)) {
					$new_name=$data->new_name??$data->name;
					$address=$data->address??NULL;
					$comment=$data->comment??NULL;
					$price_round=$data->price_round??0;
					$is_disabled=$data->is_disabled??0;
					$province_id=$ordDb->read_id_by_name("provinces", $data->province)["value"]; 
					output($ordDb->update("stores", array("name"=>$new_name, "logo"=>$data->logo, "province_id"=>$province_id, "address"=>$address, "comment"=>$comment, "price_round"=>$price_round, "is_disabled"=>$is_disabled), array("name"=>$data->name)));
				}
			else
				output_missed("logo");
		else
			output_missed("name");
		break;
	case "update-store-disabled":
		if(isset($data->name)) {
			$is_disabled=$data->is_disabled??0;
			output($ordDb->update("stores", array("is_disabled"=>$is_disabled), array("name"=>$data->name)));
		}
		else
			output_missed("name");
		break;
	case "update-store-city-enabled":
	    $condition=null;
		if(isset($data->name)) $condition=array("name"=>$data->name);
		$is_city_enabled=$data->is_city_enabled??0;
		output($ordDb->update("stores", array("is_city_enabled"=>$is_city_enabled), $condition));
		break;
	case "read-stores-all-city-enabled":
		$count=$ordDb->read_scalar("stores", "count(id)", array("is_city_enabled"=>1), true)["value"];
		output(array("value"=>$count));
		break;
	case "drop-store":
		if(isset($data->name))
			output($ordDb->drop("stores", array("name"=>$data->name)));
		else
			output_missed("name");
		break;
	case "read-statuses":
		output($ordDb->read("statuses_view", array("name")));
		break;
	case "read-shipmethods":
		output($ordDb->read("shipmethods", array("name")));
		break;
	case "read-orders-count":
		$condition=array();

		if(isset($data->status) && $data->status) 
			$condition["status"]=$data->status;
		if(isset($data->search) && $data->search)
			$condition["id like"]=$data->search;

		output($ordDb->read_scalar("orders_view", "count(id)", $condition, true));
		break;
	case "read-orders":
		$condition=array();
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;

		if(isset($data->status) && $data->status) 
			$condition["status"]=$data->status;
		if(isset($data->search) && $data->search)
			$condition["id like"]=$data->search;

		if(!$data->export)
			output($ordDb->read("orders_view", array("id", "user", "name", "address", "ordered_at", "shipped_at", "status", "transport_cost", "sold", "remark", "sum_total_price", "shipmethod", "item_count"), $condition, $length, $offset));
		else
			export("الطلبات.csv", $ordDb->read("orders_view", array("user AS 'الزبون'", "ordered_at AS 'تاريخ الطلبية'", "shipped_at AS 'تاريخ الشحن'", "status AS 'الحالة'", "transport_cost AS 'تكلفة النقل'", "sum_total_price AS 'المجموع'", "shipmethod AS 'طريقة الشحن'", "item_count AS 'عدد الأقلام'")));
			
		break;
	case "update-order":
		if(isset($data->order_id)) 
			if(isset($data->status)) {
				if(isset($data->shipmethod) && $data->shipmethod) 
					$shipmethod_id=$ordDb->read_id_by_name("shipmethods", $data->shipmethod)["value"];
				else
					$shipmethod_id=NULL;

				if(isset($data->transport_cost) && $data->transport_cost)
					$transport_cost=$data->transport_cost;
				else
					$transport_cost=0;

				if(isset($data->shipped_at) && $data->shipped_at)
					$shipped_at=$data->shipped_at;
				else
					$shipped_at=NULL;

				$status_id=$ordDb->read_id_by_name("statuses", $data->status)["value"];
				if($status_id==CANCEL_STATUS) {
					$products=$ordDb->read("ordered_products", array("product_id", "variant", "size", "count"), array("order_id"=>$data->order_id));
					foreach($products as $product) {
						$size_id=NULL;
						if($product["size"]!="" && $product["size"]!=NULL)
							$size_id=$ordDb->read_id_by_name("sizes", $product["size"])["value"];
						$image_id=NULL;
						if($product["variant"]!="" && $product["variant"]!=NULL)
							$image_id=$ordDb->read_scalar("product_images", "id", array(array("product_id"=>$product["product_id"], "variant"=>$product["variant"])),true)["value"];
						$ordDb->update("warehouse", array("count"=>"`count`+".$product["count"]), array(array("product_id"=>$product["product_id"], "size_id"=>$size_id, "product_images_id"=>$image_id))); 
					}
				}
				output($ordDb->update("orders", array("status_id"=>$status_id, "shipped_at"=>$shipped_at, "shipmethod_id"=>$shipmethod_id, "transport_cost"=>$transport_cost), array("id"=>$data->order_id)));
			}
			else
				output_missed("status");
		else
			output_missed("order id");
		break;
	case "read-ordered-products-by-order":
		if(isset($data->order_id)) 
			output($ordDb->read("ordered_products_view", array("id", "order_id", "product_id", "product", "store", "price", "count", "variant", "size", "sold", "remark", "total_price"), array("order_id"=>$data->order_id)));
		else
			output_missed("order_id");
		break;
	case "read-products":
		if(isset($data->store)) {
			$store_id=$ordDb->read_id_by_name("stores", $data->store)["value"];
			output($ordDb->read("products", array("id", "name"), array("store_id"=>$store_id)));
		}
		else
			output_missed("store");
		break;
	case "read-product":
		if(isset($data->product_id))
			output($ordDb->read("products_view", array("name", "logo", "category", "subcategory", "store"), array("id"=>$data->product_id)));
		else
			output_missed("product_id");
		break;
	case "read-product-variant-image":
		if(isset($data->product_id))
			if(isset($data->variant))
				output($ordDb->read_scalar("product_images", "src", array(array("product_id"=>$data->product_id, "variant"=>$data->variant))));
			else
				output_missed("variant");
		else
			output_missed("product_id");
		break;
	case "read-ads-products":
		output($ordDb->read("ads_products", array("ad_id", "link", "image_url", "product"), NULL));
		break;
	case "create-ads-product":
		if(isset($data->link))
			if(isset($data->image_url)) 
				if(isset($data->product)) 
					if(isset($data->store)) {
						$store_id=$ordDb->read_id_by_name("stores", $data->store)["value"];
						$product_id=$ordDb->read_scalar("products", "id", array(array("name"=>$data->product, "store_id"=>$store_id)))["value"];

					if($product_id) {
						$id=$ordDb->create("ads", array("link"=>$data->link, "image_url"=>$data->image_url))["value"];
						output($ordDb->create("ads_products", array("ad_id"=>$id, "product_id"=>$product_id)));
					}
					else
						output_missed($data->product);
				}
				else
					output_missed("product");
			else
				output_missed("image_url");
		else
			output_missed("link");
		break;
	case "read-ads-categories":
		output($ordDb->read("ads_categories", array("ad_id", "link", "image_url", "category"), NULL));
		break;
	case "create-ads-category":
		if(isset($data->link))
			if(isset($data->image_url)) 
				if(isset($data->category)) {
					$category_id=$ordDb->read_id_by_name("categories", $data->category)["value"];
					if($category_id) {
						$id=$ordDb->create("ads", array("link"=>$link, "image_url"=>$data->image_url))["value"];
						output($ordDb->create("ads_categories", array("ad_id"=>$id, "category_id"=>$category_id)));
					}
					else
						output_missed($data->category);
				}
				else
					output_missed("category");
			else
				output_missed("image_url");
		else
			output_missed("link");
		break;
	case "read-ads-stores":
		output($ordDb->read("ads_stores", array("ad_id", "link", "image_url", "store"), NULL));
		break;
	case "create-ads-store":
		if(isset($data->link))
			if(isset($data->image_url)) 
				if(isset($data->store)) {
					$store_id=$ordDb->read_id_by_name("stores", $data->store)["value"];
					if($store_id) {
						$id=$ordDb->create("ads", array("link"=>$link, "image_url"=>$data->image_url))["value"];
						output($ordDb->create("ads_stores", array("ad_id"=>$id, "store_id"=>$store_id)));
					}
					else
						output_missed($data->store);
				}
				else
					output_missed("store");
			else
				output_missed("image_url");
		else
			output_missed("link");
		break;
	case "read-ads-pages":
		output($ordDb->read("ads_pages_view", array("ad_id", "link", "image_url", "page_name"), NULL));
		break;
	case "create-ads-page":
		if(isset($data->link))
			if(isset($data->image_url)) 
				if(isset($data->page)) {
					$id=$ordDb->create("ads", array("link"=>$data->link, "image_url"=>$data->image_url))["value"];
					output($ordDb->create("ads_pages", array("ad_id"=>$id, "page_name"=>$data->page)));
				}
				else
					output_missed("page");
			else
				output_missed("image_url");
		else
			output_missed("link");
		break;
	case "drop-ads":
		if(isset($data->ad_id))
			output($ordDb->drop("ads", array("id"=>$data->ad_id)));
		else
			output_missed("ad_id");
		break;
	case "read-coupons-global":
		output($ordDb->read("coupons_global_full_view", array("code", "valid_from", "valid_to", "sold_amount", "is_sold_percent", "purchase_count", "max_count", "is_infinity", "is_user_ultimate", "is_disabled"), NULL));
		break;
	case "create-coupon-general":
		if(isset($data->code))
			if(isset($data->sold_amount)) 
				if(isset($data->valid_to)) 
					output($ordDb->create("coupons", array("code"=>$data->code, "valid_to"=>$data->valid_to, "valid_from"=>$data->valid_from, "sold_amount"=>$data->sold_amount, "is_sold_percent"=>$data->is_sold_percent, "max_count"=>$data->max_count, "is_infinity"=>$data->is_infinity, "is_global_code"=>1, "is_user_ultimate"=>$data->is_user_ultimate, "is_disabled"=>$data->is_disabled)));
				else
					output_missed("valid_to");
			else
				output_missed("sold_amount");
		else
			output_missed("code");
		break;
	case "read-coupons-store":
		output($ordDb->read("coupons_stores_full_view", array("code", "valid_from", "valid_to", "sold_amount", "is_sold_percent", "purchase_count", "max_count", "is_infinity", "is_user_ultimate", "is_disabled", "stores"), NULL));
		break;
	case "create-coupon-stores":
		if(isset($data->code))
			if(isset($data->sold_amount)) 
				if(isset($data->valid_to)) 
					if(isset($data->stores)) {
						$id_result=$ordDb->create("coupons", array("code"=>$data->code, "valid_to"=>$data->valid_to, "valid_from"=>$data->valid_from, "sold_amount"=>$data->sold_amount, "is_sold_percent"=>$data->is_sold_percent, "max_count"=>$data->max_count, "is_infinity"=>$data->is_infinity, "is_user_ultimate"=>$data->is_user_ultimate, "is_disabled"=>$data->is_disabled));
						if($id_result["status"]=="success") {
    						foreach($data->stores as $store) {
    							$store_id=$ordDb->read_id_by_name("stores", $store)["value"];
    							if($store_id) 
    								$ordDb->create("coupons_stores", array("coupon_id"=>$id_result["value"], "store_id"=>$store_id));
    							else
    								output_missed($data->store);
    							}
						}
						output($id_result);
					}
					else
						output_missed("stores");
				else
					output_missed("valid_to");
			else
				output_missed("sold_amount");
		else
			output_missed("code");
		break;
	case "read-coupons-subcategory":
		output($ordDb->read("coupons_subcategories_full_view", array("code", "valid_from", "valid_to", "sold_amount", "is_sold_percent", "purchase_count", "max_count", "is_infinity","is_user_ultimate", "is_disabled", "store", "subcategories"), NULL));
		break;
	case "create-coupon-subcategories":
		if(isset($data->code))
			if(isset($data->sold_amount)) 
				if(isset($data->valid_to)) 
					if(isset($data->subcategories)) {
						$id_result=$ordDb->create("coupons", array("code"=>$data->code, "valid_to"=>$data->valid_to, "valid_from"=>$data->valid_from, "sold_amount"=>$data->sold_amount, "is_sold_percent"=>$data->is_sold_percent, "max_count"=>$data->max_count, "is_infinity"=>$data->is_infinity, "is_user_ultimate"=>$data->is_user_ultimate, "is_disabled"=>$data->is_disabled));
						if($id_result["status"]=="success") {
    						$store_id=$ordDb->read_id_by_name("stores", $data->store_subcategory)["value"];
    						foreach($data->subcategories as $subcategory) {
							$subcategory_array=explode(":", $subcategory);
    							$category_id=$ordDb->read_id_by_name("categories", $subcategory_aray[0])["value"];
    							$subcategory_id=$ordDb->read_scalar("subcategories", "id", array("name"=>$subcategory_array[1], "category_id"=>$category_id))["value"];
    							if($subcategory_id) 
    								$ordDb->create("coupons_subcategories", array("coupon_id"=>$id_result["value"], "subcategory_id"=>$subcategory_id, "store_id"=>$store_id));
    							else
    								output_missed($subcategory);
    							}
						}
						output($id_result);
					}
					else
						output_missed("subcategories");
				else
					output_missed("valid_to");
			else
				output_missed("sold_amount");
		else
			output_missed("code");
		break;
	case "drop-coupon":
		if(isset($data->code))
			output($ordDb->drop("coupons", array("code"=>$data->code)));
		else
			output_missed("code");
		break;
	case "update-coupon":
		if(isset($data->code))
			output($ordDb->update("coupons", array("is_disabled"=>$data->is_disabled), array("code"=>$data->code)));
		else
			output_missed("code");
		break;
	case "read-coupon-denied-users":
		output($ordDb->read("coupons_users_denied_view", array("user", "name", "denied_at"),null));
		break;
	case "create-coupon-denied-user":
		if(isset($data->user)) {
			$user_result=$ordDb->read_scalar("users", "id", array("user"=>$data->user));
			if($user_result["status"]=="success")
				output($ordDb->create("coupons_users_denied", array("user_id"=>$user_result["value"])));
			else
				output_missed($data->user);
		}
		else
			output_missed("user");
		break;
	case "drop-coupon-denied-user":
		if(isset($data->user)) {
			$user_result=$ordDb->read_scalar("users", "id", array("user"=>$data->user));
			if($user_result["status"]=="success")
				output($ordDb->drop("coupons_users_denied", array("user_id"=>$user_result["value"])));
			else
				output_missed($data->user);
		}
		else
			output_missed("user");
		break;
	case "read-settings":
		$condition=NULL;
		if(isset($data->admin_settings) && $data->admin_settings)
			$user_id=NULL;
		else 
			$user_id=$ordDb->read_id_by_user("users", $_SESSION["user"])["value"];

		$condition=array("user_id"=>$user_id);

		output($ordDb->read("settings", array("name", "value"), $condition));
		break;
	case "update-settings":
		if(isset($data->settings) && count($data->settings)>0) {
			$conditions=array();
			$result=NULL;

			if(isset($data->admin_settings) && $data->admin_settings)
				$user_id=NULL;
			else 
				$user_id=$ordDb->read_id_by_user("users", $_SESSION["user"])["value"];

			$conditions["user_id"]=$user_id;

			foreach($data->settings as $setting) {
				if($result==NULL || $result["status"]=="success") {	
					$conditions["name"]=$setting->name;
					$setting_result=$ordDb->read_scalar("settings", "id", array($conditions));
					if($setting_result["value"]==NULL)
						$result=$ordDb->create("settings", array("name"=>$setting->name, "value"=>$setting->value, "user_id"=>$user_id));
					else
						$result=$ordDb->update("settings", array("value"=>$setting->value), array("id"=>$setting_result["value"]));
				}
			}

			output($result);
		}
		else
			output_missed("settings");
		break;
	case "shadow":
		$_SESSION["store"]=$data->store;
		output(array("status"=>"success")); 
		break;
	default:
		output(array("status"=>"failed", "msg"=>"$operation is not supported"));
	}
}

function output($value) {
	echo json_encode($value, JSON_UNESCAPED_UNICODE);
}

function export($file_name, $value) {
	if($path=Exporter::export($file_name, $value))
		output(array("status"=> "success", "msg"=> $path));
	else
		output(array("status"=> "error"));
}

function output_missed($value) {
	output(array("status"=>"error", "msg"=>$value . " not exists."));
	die();
}

function output_invalid($value) {
	output(array("status"=>"error", "msg"=>"($value) is invalid."));
	die();
}

function output_limit_access() {
	$user=$_SESSION["user"]??"<unknown user>";
	output(array("status"=>"error", "msg"=>$user . " doesn't has access permissions."));
	die();
}

function operation_destroy_session($session_key) {
		$curl=curl_init();
		curl_setopt($curl, CURLOPT_URL, GET_SITEURL . "backend/session.php");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, array("session_key"=>$session_key));
		curl_exec($curl);
}
