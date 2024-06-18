<?php
if($_SERVER["REQUEST_METHOD"]=="POST") {
	include "orm-db.php";

	$ordDb=new OrmDb();
	$data=json_decode(file_get_contents("php://input"));
	if($data==NULL) output_input_error();
	
	$operation=$data->operation;

	if(isset($_COOKIE["PHPSESSID"])) 
		session_id($_COOKIE["PHPSESSID"]);
	elseif(isset($data->session_id))  
		session_id($data->session_id);

	session_start();

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	if(!isset($_SESSION["user_level"]) && 
		$operation!="create-user" &&
		$operation!="create-user-code-confirm" && 
		$operation!="update-user-activate" &&
		$operation!="create-password-code-confirm" &&
		$operation!="confirm-password-code" &&
		$operation!="update-user-password" 
	)
		output_limit_access();


	switch($operation) {
	case "create-user":
		$user=$data->user;
		$password=$data->password;
		$phone=$user;
		$province=$data->province??NULL;
		$address=$data->address??NULL;
		$name=$data->name??NULL;

		$phone_verification=$ordDb->read_scalar("settings", "value", array(array("name"=>"phone-verification", "user_id"=>NULL)), true)["value"];

		$role_id=$ordDb->read_scalar("roles", "id", array("user_level"=>CLIENT_USER3_LEVEL), true)["value"];
		if($province!=NULL) {
			$province_result=$ordDb->read_id_by_name("provinces", $province);
			$province_id=$province_result["value"];
		}
		else
			$province_id=NULL;

		$not_activated_user=$ordDb->read_scalar("users", "id", array(array("user"=>$user, "is_confirmed"=>0)));
		if($not_activated_user["value"]=="") { 
			$user_result=$ordDb->create("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>$role_id, "name"=>$name, "is_disabled"=>1, "is_confirmed"=>1-$phone_verification));

			if($user_result["status"]=="success") {
				$user_id=$user_result["value"];
				$ordDb->create("userinfo", array("user_id"=>$user_id, "phone"=>$phone, "province_id"=>$province_id, "address"=>$address));
				output(array("status"=>"success", "msg"=>$phone_verification==1?"verification code":"user added"));
			}
			else
				output($user_result);
		}
		else {
			$user_result=$ordDb->update("users", array("user"=>$user, "code"=>password_hash($password, PASSWORD_DEFAULT), "role_id"=>$role_id, "name"=>$name), array("id"=>$not_activated_user["value"]));
			if($user_result["status"]=="success") {
				$ordDb->update("userinfo", array("phone"=>$phone, "province_id"=>$province_id, "address"=>$address), array("user_id"=>$not_activated_user["value"]));
				output(array("status"=>"success", "msg"=>$phone_verification==1?"verification code":"user updated"));
			}
			else
				output($user_result);

		}
		break;
	case "create-user-code-confirm":
		if(isset($data->user)) {
			$code=generateRandomString(5);
			$user_result=$ordDb->read_id_by_user("users", array("user"=>$data->user));
			if($user_result["status"]=="success") {
				$gsm_user="963" . substr($data->user, 1);
				$curl=curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://mobileservice.com/Gsm=$gsm_user&Msg=verification%20code:%20$code&Lang=1");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result=curl_exec($curl);
				curl_close($curl);

				$result=$ordDb->create("users_code", array("user_id"=>$user_result["value"], "activation_code"=>$code, "is_creating"=>1));
				if($result["status"]=="success")
					output(array("status"=>"success", "value"=>$data->user, "msg"=>"confirm code has been send"));
				else
					output($result);
			}
			else
				output_missed($data->user);
		}
		else
			output_missed("user");
		break;
	case "update-user-activate":
		if(isset($data->user))
			if(isset($data->code)) {
				$result=$ordDb->read_scalar("users_code_create_view", "activation_code", array(array("activation_code"=>$data->code, "user"=>$data->user)));
				if($result["value"]!="") {
					$user_result=$ordDb->read_id_by_user("users", array("user"=>$data->user));
					$ordDb->update("users", array("is_confirmed"=>1), array("id"=>$user_result["value"]));
					output(array("status"=>"success", "value"=>$data->user, "msg"=>"user has activated"));
				}
				else
					output(array("status"=>"error", "value"=>$data->user, "msg"=>"code error"));
			}
			else
				output_missed("code");
		else
			output_missed("user");
		break;
	case "create-password-code-confirm":
		if(isset($data->user)) {
			$code=generateRandomString(5);
			$user_result=$ordDb->read_id_by_user("users", array("user"=>$data->user));
			if($user_result["status"]=="success") {
				$gsm_user="963" . substr($data->user, 1);
				$curl=curl_init();
				curl_setopt($curl, CURLOPT_URL, "https://mobileservice.com?Gsm=$gsm_user&Msg=verification%20code:%20$code&Lang=1");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result=curl_exec($curl);
				curl_close($curl);

				$result=$ordDb->create("users_code", array("user_id"=>$user_result["value"], "activation_code"=>$code, "is_creating"=>0));
				if($result["status"]=="success")
					output(array("status"=>"success", "value"=>$data->user, "msg"=>"confirm code has been send"));
				else
					output($result);
			}
			else
				output_missed($data->user);
		}
		else
			output_missed("user");
		break;
	case "confirm-password-code":
		if(isset($data->user))
			if(isset($data->code)) {
				$result=$ordDb->read_scalar("users_code_password_view", "activation_code", array(array("activation_code"=>$data->code, "user"=>$data->user)));
				if($result["value"]!="") 
					output(array("status"=>"success", "value"=>$data->user, "msg"=>"code was confirmed"));
				else
					output(array("status"=>"error", "value"=>$data->user, "msg"=>"code error"));
			}
			else
				output_missed("code");
		else
			output_missed("user");
		break;
	case "update-user-password":
		if(isset($data->user))
			if(isset($data->password))
				if(isset($data->code)) {
					$result=$ordDb->read_scalar("users_code_password_view", "activation_code", array(array("activation_code"=>$data->code, "user"=>$data->user)));
					if($result["value"]!="") {
						$user_result=$ordDb->read_id_by_user("users", array("user"=>$data->user));
						$ordDb->update("users", array("code"=>password_hash($data->password, PASSWORD_DEFAULT)), array("id"=>$user_result["value"]));
						output(array("status"=>"success", "value"=>$data->user, "msg"=>"password has updated"));
					}
					else
						output(array("status"=>"error", "value"=>$data->user, "msg"=>"code error"));
				}
				else
					output_missed("code");
			else
				output_missed("password");
		else
			output_missed("user");
		break;
	case "read-userinfo":
		output($ordDb->read("users_view", array("user", "name", "phone", "address", "province"),array("user"=>$_SESSION["user"])));
		break;
	case "read-stores-count":
		output($ordDb->read_scalar("stores_enabled_view", "count(id)", true));
		break;
	case "read-stores":
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;
		$search=$data->search??NULL;
		$export=$data->export??NULL;

		$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
		
		$conditions=array("is_city_enabled"=>1, "province_id!"=>$user_province);
		
		if($search!=NULL) 
		    $conditions=array(array($conditions, array("name like"=>$search, "comment like"=>$search, "address like"=>$search)));

		output($ordDb->read("stores_enabled_random_view", array("name", "logo"), $conditions, $length, $offset), $export);
		break;
	case "read-categories":
		output($ordDb->read("categories", array("name AS category")));
		break;
	case "read-categories-of-store":
		if(isset($data->store))
			output($ordDb->read("store_categories_view", array("category"), array("store"=>$data->store)));
		else
			output_missed("store");
		break;
	case "read-subcategories-of-store":
		if(isset($data->store))	
			if(isset($data->category))
				output($ordDb->read("store_subcategories_view", array("subcategory"), array(array("store"=>$data->store, "category"=>$data->category))));
			else
				output_missed("category");
		else
			output_missed("store");
		break;
	case "read-subcategories":
		if(isset($data->category)) {
			$category_result=$ordDb->read_id_by_name("categories", $data->category);
			if($category_result["status"]=="success")
				output($ordDb->read("subcategories", array("name AS subcategory"), array("category_id"=>$category_result["value"])));
			else
				output_missed($data->category);
		}
		else
			output_missed("category");
		break;
	case "read-warehouse-count":
		if(isset($data->product_id)) {
			$variant=$data->variant??NULL;
			$size=$data->size??NULL;

			if(!isset($data->variant) || $data->variant==NULL) $variant="";
			if(!isset($data->size) || $data->size==NULL) $size="";

			output($ordDb->read_scalar("warehouse_view", "count", array(array("product_id"=>$data->product_id, "variant"=>$variant, "size"=>$size)), true));
		}
		else
			output_missed("product_id");
		break;
	case "read-products":
		$conditions=array();
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;
		
		$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
		
		$conditions[]=array("is_city_enabled"=>1, "province_id!"=>$user_province);
		$conditions["is_sold"]=0;
		$conditions["is_offer"]=0;
		if($data->store!=NULL) $conditions["store"]=$data->store;
		if($data->category!=NULL) $conditions["category"]=$data->category;
		if($data->subcategory!=NULL) $conditions["subcategory"]=$data->subcategory;
		if($data->search!=NULL)	$conditions[]=array("name like"=>$data->search, "comment like"=>$data->search);

		$products=$ordDb->read("products_enabled_view", array("id", "name", "type", "category", "subcategory", "store", "logo", getPriceFieldName() . " AS price", "comment", "created_at", "is_sold"), array($conditions), $length, $offset);
		for($i=0; $i<count($products); $i++) {
			if($ordDb->read_scalar("favorites", "added_at", array(array("user_id"=>$_SESSION["id"], "product_id"=>$products[$i]["id"])))["value"]!=NULL)
				$products[$i]["is_favorite"]=1;
			else
				$products[$i]["is_favorite"]=0;

			if($products[$i]["is_sold"]) {
				$sold_amount=$ordDb->read_scalar("solds", 'CONCAT(sold_amount, IF(is_sold_percent, "%", "")) AS sold', array("product_id"=>$products[$i]["id"]))["value"];
				if(strpos($sold_amount, "%"))
					$products[$i]["price"]*=(100-intval(substr($sold_amount, 0, -1)))/100;
				else
					$products[$i]["price"]-=$sold_amount;
			}
		}

		output($products);
		break;
	case "read-products-offer":
		$conditions=array();
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;

		$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
		
		$conditions[]=array("is_city_enabled"=>1, "province_id!"=>$user_province);
		if($data->store!=NULL) $conditions["store"]=$data->store;
		if($data->category!=NULL) $conditions["category"]=$data->category;
		if($data->search!=NULL)	$conditions[]=array("name like"=>$data->search, "comment like"=>$data->search);
		$products=$ordDb->read("offers_view", array("id", "name", "type", "category", "subcategory", "store", "logo", getPriceFieldName() . " AS price", "comment", "created_at", "started_at", "end_at"), array($conditions), $length, $offset);
		for($i=0; $i<count($products); $i++)
			if($ordDb->read_scalar("favorites", "added_at", array(array("user_id"=>$_SESSION["id"], "product_id"=>$products[$i]["id"])))["value"]!=NULL)
				$products[$i]["is_favorite"]=1;
			else
				$products[$i]["is_favorite"]=0;
		output($products);
		break;
	case "read-products-sold":
		$conditions=array();
		$length=$data->length??NULL;
		$offset=$data->offset??NULL;
		$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
		
		$conditions[]=array("is_city_enabled"=>1, "province_id!"=>$user_province);


		if($data->store!=NULL) $conditions["store"]=$data->store;
		if($data->search!=NULL)	$conditions[]=array("name like"=>$data->search, "comment like"=>$data->search);
		$products=$ordDb->read("solds_view", array("id", "name", "type", "category", "subcategory", "store", "logo", "old_" . getPriceFieldName() . " AS old_price", "new_" . getPriceFieldName() . " AS new_price", "comment", "created_at", "started_at", "end_at", "sold_amount_type"), array($conditions), $length, $offset);
		for($i=0; $i<count($products); $i++)
			if($ordDb->read_scalar("favorites", "added_at", array(array("user_id"=>$_SESSION["id"], "product_id"=>$products[$i]["id"])))["value"]!=NULL)
				$products[$i]["is_favorite"]=1;
			else
				$products[$i]["is_favorite"]=0;
		output($products);
		break;
	case "read-product-details":
		if(isset($data->product_id)) {
			$id=$data->product_id;
			$products=$ordDb->read("products_view", array("id", "name", "type", "category", "subcategory", "store", "logo", getPriceFieldName() . " AS price", "comment", "created_at", "is_sold"), array("id"=>$id))[0];
			if(count($products)>0) {
				$product=array();
				$product["product"]=$products;

				if($ordDb->read_scalar("favorites", "added_at", array(array("user_id"=>$_SESSION["id"], "product_id"=>$id)))["value"]!=NULL)
					$product["product"]["is_favorite"]=1;
				else
					$product["product"]["is_favorite"]=0;

				$product["images"]=$ordDb->read("product_images", array("src", "variant"), array("product_id"=>$id));
				$product["warehouse"]=$ordDb->read("warehouse_available_view", array("variant", "size", "count"), array("product_id"=>$id));
				if($products["is_offer"]=="1")
					$product["offer"]=$ordDb->read("offers", array("started_at", "end_at"), array("product_id"=>$id))[0];
				else
					$product["offer"]=NULL;
				if($products["is_sold"]=="1") {
					$product["sold"]=$ordDb->read("solds_view", array("started_at", "end_at", "old_" . getPriceFieldName() . " AS old_price", "new_" . getPriceFieldName() . " AS new_price", "sold_amount_type"), array("id"=>$id))[0];
					$product["product"]["price"]=$product["sold"]["new_price"];
				}
				else
					$product["sold"]=NULL;
			       output($product);	
			}
			else
				output_missed("no values, product id: $id");
		}
		else
			output_missed("product id");
		break;
	case "read-favorites":
		output($ordDb->read("favorites_view", array("product_id", "name", "logo", getPriceFieldName() . " AS price", "added_at"), array("user"=>$_SESSION["user"]), $length, $offset));
		break;
	case "create-favorite":
		if(isset($data->product_id)) {
			output($ordDb->create("favorites", array("user_id"=>$_SESSION["id"], "product_id"=>$data->product_id)));
		}
		else
			output_missed("product id");
		break;
	case "drop-favorite":
		if(isset($data->product_id)) 
			output($ordDb->drop("favorites", array(array("user_id"=>$_SESSION["id"], "product_id"=>$data->product_id))));
		else
			output_missed("product id");
		break;
	case "read-orders":
		$orders=$ordDb->read("orders_view", array("id", "ordered_at", "shipped_at", "transport_cost", "status", "shipmethod", "sum_total_price - sold AS sum_total_price", "item_count", "sold"), array("user"=>$_SESSION["user"]));
		for($i=0; $i<count($orders); $i++)
			if($orders[$i]["shipmethod"]=="شحن")
				$orders[$i]["transport_message"]=$ordDb->read_scalar("settings", "value", array(array("name"=>"transport-message", "user_id"=>NULL)))["value"];
			else
				$orders[$i]["transport_message"]="";

		output($orders);
		break;
	case "read-orders-shopping":
		output($ordDb->read("orders_shopping_view", array("id", "ordered_at", "shipped_at", "transport_cost", "status", "sum_total_price", "item_count"), array("user"=>$_SESSION["user"])));
		break;
	case "create-order":
		if(isset($data->products) && count($data->products)>0) { 
			$products=$data->products;
			$empty_stock="";
			for($i=0; $i<count($products); $i++) {
				$size=$products[$i]->size??"";
				$variant=$products[$i]->variant??"";
				$warehouse_result=$ordDb->read_scalar("warehouse_view", "id", array(array("product_id"=>$products[$i]->product_id, "size"=>$size, "variant"=>$variant)), true);
				if($warehouse_result["value"]!=NULL) {
					$count=$ordDb->read_scalar("warehouse", "count", array("id"=>$warehouse_result["value"]), true)["value"];
					if($count>=$products[$i]->count)
						$products[$i]->warehouse_id=$warehouse_result["value"];
					else {
						$product_name=$ordDb->read_scalar("products", "name", array($products[$i]->$product_id), false)["value"];
						$empty_stock.=$product_name."$".$count.";";
					}
				}
				else { 
					output(array("status"=>"failed", "msg"=>"internal error"));
					die();
				}
			}

			if($empty_stock==="") {
				$order=$ordDb->create("orders", array("user_id"=>$_SESSION["id"], "status_id"=>ONHOLD_STATUS));
				$order_id=$order["value"];
				foreach($products as $product) { 
					$ordDb->create("ordered_products", array("order_id"=>$order_id, "product_id"=>$product->product_id, "count"=>$product->count, "price"=>$product->price, "size"=>$product->size==""?NULL:$product->size, "variant"=>$product->variant));
					$ordDb->update("warehouse", array("count"=>"`count`-".$product->count), array("id"=>$product->warehouse_id));
				}
				output($order);
			}
			else
				output(array("status"=>"failed", "msg"=>"amount not in stock", "value"=>$empty_stock));
		}
		else
			output_missed("products");
		break;
	case "create-ordered-product":
		if(isset($data->product_id))
			if(isset($data->price)) {

				$shopping_ban=$ordDb->read_scalar("settings", "value", array(array("name"=>"city-shopping-ban", "user_id"=>NULL)), true)["value"];
				if($shopping_ban==1) {
					$user_province_id=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
					$store_id=$ordDb->read_scalar("products", "store_id", array("id"=>$data->product_id), true)["value"];
					$store_province_id=$ordDb->read_scalar("stores", "province_id", array("id"=>$store_id), true)["value"];
					if($user_province_id==$store_province_id) {
						$shopping_ban_message=$ordDb->read_scalar("settings", "value", array(array("name"=>"shopping-ban-message", "user_id"=>NULL)))["value"];
						output(array("status"=>"failed", "msg"=>$shopping_ban_message, "value"=>$data->product_id));
						break;
					}
				}

				$count=$data->count??1;

				$size=NULL;
				if(isset($data->size) && $data->size!=="")
					$size=$data->size;

				$variant=NULL;
				if(isset($data->variant) && $data->variant!=="")
					$variant=$data->variant;

				$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)), true)["value"];
				if(!$order_id) {
					$transport_cost=$ordDb->read_scalar("settings", "value", array("name"=>"transport-cost"), true)["value"];
					$order_id=$ordDb->create("orders", array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS, "transport_cost"=>$transport_cost))["value"];
				}

				$warehouse_id=$ordDb->read_scalar("warehouse_view", "id", array(array("product_id"=>$data->product_id, "size"=>$size!=NULL?$size:"", "variant"=>$variant!=NULL?$variant:"")), true)["value"];
				if($warehouse_id) {
					$warehouse_count=$ordDb->read_scalar("warehouse", "count", array("id"=>$warehouse_id), true)["value"];
					if($warehouse_count-$count>=0) {
						$duplicate_id=$ordDb->read_scalar("ordered_products", "id", array(array("order_id"=>$order_id, "product_id"=>$data->product_id, "price"=>$data->price, "size"=>$size, "variant"=>$variant)), true)["value"];
						if(!$duplicate_id)
							$result=$ordDb->create("ordered_products", array("order_id"=>$order_id, "product_id"=>$data->product_id, "count"=>$count, "price"=>$data->price, "size"=>$size, "variant"=>$variant));
						else
							$result=$ordDb->update("ordered_products", array("count"=>"`count`+".$count), array("id"=>$duplicate_id));
						$ordDb->update("warehouse", array("count"=>"`count`-".$count), array("id"=>$warehouse_id));
					}
					else
						output_error("no enough quantity in warehouse");
				}
				else
					output_error("item not exists in warehouse");

				output($result);
			}
			else
				output_missed("price");
		else
			output_missed("product_id");
		break;
	case "update-ordered-product-decrease":
		if(isset($data->product_id))
			if(isset($data->count)) {
				$size=NULL;
				if(isset($data->size) && $data->size!=="")
					$size=$data->size;

				$variant=NULL;
				if(isset($data->variant) && $data->variant!=="")
					$variant=$data->variant;

				$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)), true)["value"];
				if($order_id) {
					$warehouse_id=$ordDb->read_scalar("warehouse_view", "id", array(array("product_id"=>$data->product_id, "size"=>$size, "variant"=>$variant)), true)["value"];
					if($warehouse_id) {
						$order_product_id=$ordDb->read_scalar("ordered_products", "id", array(array("order_id"=>$order_id, "product_id"=>$data->product_id, "price"=>$data->price, "size"=>$size, "variant"=>$variant), true))["value"];
						if($order_product_id) {
							$result=$ordDb->update("ordered_products", array("count"=>"`count`-".$data->count), array("id"=>$order_product_id));
							$ordDb->update("warehouse", array("count"=>"`count`+".$data->count), array("id"=>$warehouse_id));
						}
					}
					else
						output_error("item not exists in warehouse");
				}
				else
					output_error("no shopping order available");

				output($result);
			}
			else
				output_missed("price");
		else
			output_missed("product_id");
		break;
	case "drop-ordered-product":
		if(isset($data->product_id)) 
			if(isset($data->count)) {
				$size=NULL;
				if(isset($data->size) && $data->size!=="")
					$size=$data->size;
				$variant=NULL;
				if(isset($data->variant) && $data->variant!=="")
					$variant=$data->variant;
				$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)), true)["value"];
				if($order_id) {
					$warehouse_id=$ordDb->read_scalar("warehouse_view", "id", array(array("product_id"=>$data->product_id, "size"=>$size!=NULL?$size:"", "variant"=>$variant!=NULL?$variant:"")), true)["value"];
					if($warehouse_id) {
						$result=$ordDb->drop("ordered_products", array(array("order_id"=>$order_id, "product_id"=>$data->product_id, "size"=>$size, "variant"=>$variant)));
						$ordDb->update("warehouse", array("count"=>"`count`+".$data->count), array("id"=>$warehouse_id));
						output($result);
					}
					else
						output_error("item not exists in warehouse");
				}
				else
					output_error("no shopping order available");
			}
			else
				output_missed("count");
		else
			output_missed("product_id");
		break;
	case "update-ordered-product":
		if(isset($data->product_id))
			if(isset($data->count)) {
				$size=NULL;
				if(isset($data->size) && $data->size!=="")
					$size=$data->size;
				$variant=NULL;
				if(isset($data->variant) && $data->variant!=="")
					$variant=$data->variant;
				$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)), true)["value"];
				if($order_id) {
					$warehouse_id=$ordDb->read_scalar("warehouse_view", "id", array(array("product_id"=>$data->product_id, "size"=>$size, "variant"=>$variant)), true)["value"];
					if($warehouse_id) {
						$warehouse_count=$ordDb->read_scalar("warehouse", "count", array("id"=>$warehouse_id), true)["value"];
						$old_count=$ordDb->read_scalar("ordered_products", "count", array(array("order_id"=>$order_id, "product_id"=>$data->product_id, "size"=>$size, "variant"=>$variant)), true)["value"];
						if($warehouse_count-($data->count-$old_count)>=0) {
							$result=$ordDb->update("ordered_products", array("count"=>$data->count), array(array("order_id"=>$order_id, "product_id"=>$data->product_id, "size"=>$size, "variant"=>$variant)));
							$ordDb->update("warehouse", array("count"=>"`count`-".($data->count-$old_count)), array("id"=>$warehouse_id));
							output($result);
						}
						else
							output_error("no enough quantity in warehouse");
					}
					else
						output_error("item not exists in warehouse");
				}
				else
					output_error("no shopping order available");
			}
			else
				output_missed("count");
		else
			output_missed("product_id");
		break;
	case "read-ordered-products":
		if(isset($data->order_id)) 
			output($ordDb->read("ordered_products_view", array("product_id", "product", "logo", "price", "count", "size", "variant", "logo", "total_price"), array("order_id"=>$data->order_id)));
		else
			output_missed("order_id");
		break;
	case "read-ordered-products-shopping-count":
		$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)))["value"];
		output($ordDb->read_scalar("ordered_products_view", "count(product_id)", array("order_id"=>$order_id)));
		break;
	case "read-ordered-products-shopping":
		$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)))["value"];
		if($order_id) {
			output($ordDb->read("ordered_products_view", array("product_id", "product",  "price", "count", "size", "variant", "logo", "total_price"), array("order_id"=>$order_id)));
		}
		else
			output_missed("no shopping order available");
		break;
	case "read-ordered-products-price":
		if(isset($data->products) && count($data->products)>0) {
			$prices=[];
			foreach($data->products as $product) {
				$price=$ordDb->read_scalar("products_view", "price", array("id"=>$product->product_id), true)["value"];	
				$prices[]=array("product_id"=>$product->product_id, "price"=>$price);
			}

			output($prices);
		}
		break;
	case "read-order-shopping-transportcost":
		$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)))["value"];
		if($order_id) {
			$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
			$store_provinces=$ordDb->read("ordered_products_view", array("province_id"), array("order_id"=>$order_id));
			$shipmethod_id=constant("SHIPMETHOD_DELIVER");
			foreach($store_provinces as $sp) {
				if(!($sp["province_id"]==$user_province || ($sp["province_id"]=="2" && $user_province=="1") || ($sp["province_id"]==1 && $user_province=="2")))
					$shipmethod_id=constant("SHIPMETHOD_SHIP");
				if($sp["province_id"]=="2" && $user_province=="1") 
					$user_province="2";
			}

			$transport_cost=$ordDb->read_scalar("settings", "value", array("name"=>"transport".$user_province), true)["value"];
			$transport_message="";

			if($shipmethod_id==constant("SHIPMETHOD_SHIP")) 
				$transport_message=$ordDb->read_scalar("settings", "value", array(array("name"=>"transport-message", "user_id"=>NULL)))["value"];

			output(array("transport_cost"=>$transport_cost, "transport_message"=>$transport_message));
		}
		else
			output_missed("no shopping order available");
		break;
	case "update-order-confirm":
		$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)))["value"];
		if($order_id) {
			$user_province=$ordDb->read_scalar("userinfo", "province_id", array("user_id"=>$_SESSION["id"]), true)["value"];
			$store_provinces=$ordDb->read("ordered_products_view", array("province_id"), array("order_id"=>$order_id));
			$shipmethod_id=constant("SHIPMETHOD_DELIVER");
			foreach($store_provinces as $sp) {
				if(!($sp["province_id"]==$user_province || ($sp["province_id"]=="2" && $user_province=="1") || ($sp["province_id"]==1 && $user_province=="2")))
					$shipmethod_id=constant("SHIPMETHOD_SHIP");
				if($sp["province_id"]=="2" && $user_province=="1") 
					$user_province="2";
			}

			$transport_cost=$ordDb->read_scalar("settings", "value", array("name"=>"transport".$user_province), true)["value"];
			$transport_message="";

			if($shipmethod_id==constant("SHIPMETHOD_DELIVER"))
				$transport_message=$ordDb->read_scalar("settings", "value", array(array("name"=>"transport-message", "user_id"=>NULL)))["value"];
			if(isset($data->code))
				$products=$ordDb->read("ordered_products_view", array("id", "product_id", "product",  "store", "subcategory", "price", "count", "total_price"), array("order_id"=>$order_id));
			
			output($ordDb->update("orders", array("status_id"=>ONHOLD_STATUS, "transport_cost"=>$transport_cost, "shipmethod_id"=>$shipmethod_id), array(array("user_id"=>$_SESSION["id"], "id"=>$order_id))));
		}
		else
			output_missed("no shopping order available");
		break;
	case "update-order-cancel":
		if(isset($data->order_id)) { 
			$update_result=$ordDb->update("orders", array("status_id"=>CANCEL_STATUS), array(array("user_id"=>$_SESSION["id"], "id"=>$data->order_id, "status_id"=>ONHOLD_STATUS)));
			if($update_result["value"]!==NULL) {
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
			output($update_result);
		}
		else
			output_missed("order id");
		break;
	case "read-ads-products":
		if(isset($data->product_id)) 
			output($ordDb->read("ads_products_view", array("link", "image_url"), array("product_id"=>$data->product_id)));
		else
			output_missed("product id");
		break;
	case "read-ads-stores":
		if(isset($data->store)) 
			output($ordDb->read("ads_stores_view", array("link", "image_url"), array("store"=>$data->store)));
		else
			output_missed("store");
		break;
	case "read-ads-categories":
		if(isset($data->category)) 
			output($ordDb->read("ads_categories_view", array("link", "image_url"), array("category"=>$data->category)));
		else
			output_missed("product id");
		break;
	case "read-ads-pages":
		if(isset($data->page_name)) 
			output($ordDb->read("ads_pages_view", array("link", "image_url"), array("page_name"=>$data->page_name)));
		else
			output_missed("page_name");
		break;
	case "read-coupon":
		if(isset($data->code)) {
			$order_id=$ordDb->read_scalar("orders", "id", array(array("user_id"=>$_SESSION["id"], "status_id"=>ONSHOPPING_STATUS)))["value"];
			if($order_id) {
				$sum_total_price=0;
				$products=$ordDb->read("ordered_products_view", array("product_id", "product",  "store", "subcategory", "price", "count", "total_price"), array("order_id"=>$order_id));
				$output_products=array();

				foreach($products as $product) {
					$total_price=$product["price"]*$product["count"];
					$output_products[]=array("product_id"=>$product["product_id"], "old_price"=>$total_price, "new_price"=>$total_price-$product["sold"]);
							$sum_total_price+=$total_price;
				}

				output(array("msg"=>$coupon_info["message"], "total_sold"=>$coupon_info["total_sold"], "products"=>$output_products));
			}
			else
				output(array("msg"=>$coupon_info["message"]));
		}
		else
			output_missed("code");
	       break;
	case "create-support-message":
		if(isset($data->title))
			if(isset($data->message))
				output($ordDb->create("support_messages", array("user_id"=>$_SESSION["id"], "title"=>$data->title, "message"=>$data->message)));
			else
				output_missed("message");
		else
			output_missed("title");
		break;
	case "read-settings-contacts":
		$phone=$ordDb->read_scalar("settings", "value", array(array("name"=>"phone", "user_id"=>NULL)))["value"];
		$address=$ordDb->read_scalar("settings", "value", array(array("name"=>"address", "user_id"=>NULL)))["value"];
		$facebook=$ordDb->read_scalar("settings", "value", array(array("name"=>"facebook-link", "user_id"=>NULL)))["value"];
		output(array("phone"=>$phone, "address"=>$address, "facebook"=>$facebook));
		break;
	case "read-settings-privacy":
		output($ordDb->read_scalar("settings", "value", array(array("name"=>"privacy", "user_id"=>NULL))));
		break;
	default:
		output(array("status"=>"failed", "msg"=>"unsupported operation"));
	}
}

function getPriceFieldName() {
	switch ($_SESSION["user_level"]) {
		case "4":
			return "price1";
		case "5":
			return "price2";
		case "6":
			return "price3";
		default:
			return "no_price_available";
	}
}

function generateRandomString($length = 8) {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) $randomString .= $characters[rand(0, $charactersLength - 1)];
	return $randomString;
}

function output($value) {
	echo json_encode($value, JSON_UNESCAPED_UNICODE);
}

function output_missed($value) {
	output(array("status"=>"error", "msg"=>$value . " not exists."), JSON_UNESCAPED_UNICODE);
	die();
}

function output_error($msg) {
	output(array("status"=>"error", "msg"=>$msg), JSON_UNESCAPED_UNICODE);
	die();
}

function output_input_error() {
	output(array("status"=>"error", "msg"=>"input is not a valid json"));
	die();
}

function output_limit_access() {
	$user=$_SESSION["user"]??"<unknown user>";
	output(array("status"=>"error", "msg"=>$user . " doesn't has access permissions."));
	die();
}
?>


