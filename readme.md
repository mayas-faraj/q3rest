## Get Started

The `OrmDb` class provides a basic foundation for interacting with a database. It offers a simpler approach compared to raw PHP queries. 
* For complex functionalities or building full-fledged RESTful APIs, consider using dedicated ORM libraries or frameworks. These offer a more structured approach and advanced features.

## Functionality:

* `OrmDb` offers methods for common CRUD (Create, Read, Update, Delete) operations on a database.
* It provides a layer of abstraction over raw PHP queries, making database interaction more convenient.
* While functional, `OrmDb` might not be the most performant solution for highly optimized, low-latency scenarios.
* Consider using dedicated Object-Relational Mapping (ORM) libraries like Doctrine or Eloquent for complex functionalities and advanced features.

## Database

This library is used with MySQL Database only. It has been tested on versions MySQL 5.0 to 8.1. The ORM does not create a database or seed values. You should do it yourself.
The ORM does not currently support creating joins. To achieve joins, you can create views in your database that represent the joined tables.

## Install Dependencies

The REST API supports Firebase notifications. To enable this functionality, you'll need to install the required dependencies using composer install in the project's root directory.

## ORM Config (`config.php`):

This file contains database connection information and optional configurations.

* **Security:** It's crucial to store sensitive information like passwords securely. Avoid defining them directly in the configuration file.
   * Use environment variables to store sensitive details. Refer to PHP documentation on `getenv()` for more information.

**Configuration Example:**

```php
<?php
define("DB_NAME", "your_database_name");
define("DB_USER", "your_database_user");
define("DB_PASSWORD", getenv('DB_PASSWORD')); // Use environment variable

// Optional configuration
define("GET_SITEURL", "http://localhost/");
```

then we should list the user roles by setting identity number for each user role, for example:

```php
define("ADMIN_USER_LEVEL", 1);
define("MANAGER_USER_LEVEL", 2);
define("LOGISTIC_USER_LEVEL", 3);
```

## Building RESTful APIs:

While the provided example using a switch statement works for basic CRUD operations, modern RESTful APIs often leverage frameworks for better structure and features.

* Frameworks like Laravel or Slim offer routing mechanisms and tools for building robust APIs.
* These frameworks handle aspects like request parsing, response formatting, and middleware for functionalities like authentication and authorization.

## Create API

We can create REST API by add php file like: api.php or admim-api.php, the api consit of switch case, each case represent one operation like: create, read, update or delete, etc...
Each case use ordDb object of OrmDb class.

**Note:** This documentation assumes you have a basic understanding of PHP and SQL.

### Class Setup

The `OrmDb` class handles database connections and queries. It requires configuration details stored in constants like `DB_HOST`, `DB_USER`, etc., which are likely defined in a separate configuration file.

### Methods

The class provides methods for various database operations:

**1. create($table, $cols):**

This method creates a new row in the specified table. 

* **Arguments:**
    * `$table`: (String) Name of the table to insert data into.
    * `$cols`: (Associative Array) An array containing key-value pairs where the key is the column name and the value is the data to be inserted.

* **Example:**

```php
$ordDb->create("users", array(
  "user" => "johndoe",
  "email" => "johndoe@example.com",
  "password" => password_hash("secret", PASSWORD_DEFAULT)
));
```

**2. create_query($table, $cols, $source_table, $source_cols):**

This method creates a new row in the specified table by copying data from another table.

* **Arguments:**
    * `$table`: (String) Name of the destination table.
    * `$cols`: (Associative Array) An array containing key-value pairs for the destination columns.
    * `$source_table`: (String) Name of the source table.
    * `$source_cols`: (Associative Array) An array containing key-value pairs for the source columns.

* **Example:**

```php
$ordDb->create_query("user_details", array("user_id", "phone"), "users", array("id", "phone"));
```

**3. read_id_by_user($table, $user):**

This method retrieves the ID of a record based on a specific user field.

* **Arguments:**
    * `$table`: (String) Name of the table to search.
    * `$user`: (String) The value to search for in the "user" field.

* **Example:**

```php
$user_id = $ordDb->read_id_by_user("accounts", "johndoe");
```

There are similar methods named `read_id_by_name($table, $name)` and `read_id_by_title($table, $title)` that can be used for searching by different column names.

**4. read_scalar($table, $col, $conditions=NULL, $is_int=FALSE):**

This method retrieves a single value from a specific column based on optional conditions.

* **Arguments:**
    * `$table`: (String) Name of the table to read from.
    * `$col`: (String) Name of the column to retrieve data from.
    * `$conditions` (Optional): (Associative Array) An array containing key-value pairs for filtering results.
    * `$is_int` (Optional): (Boolean) Set to `true` if the expected value is an integer.

* **Example 1 (without conditions):**

```php
$website_name = $ordDb->read_scalar("settings", "value", array("name" => "website_name"));
```

* **Example 2 (with conditions):**

```php
$user_email = $ordDb->read_scalar("users", "email", array("id" => $user_id));
```

**5. read($table, $cols, $conditions=NULL, $length=NULL, $offset=NULL):**

This method retrieves multiple rows from a table based on optional conditions and pagination.

* **Arguments:**
    * `$table`: (String) Name of the table to read from.
    * `$cols`: (Array) An array containing the names of columns to be retrieved.
    * `$conditions` (Optional): (Associative Array) An array containing key-value pairs for filtering results.
    * `$length` (Optional): (Integer) Limits the number of rows returned.
    * `$offset` (Optional): (Integer) Specifies the offset for pagination.

* **Example 1 (read all columns without conditions):**

```php
$products = $ordDb->read("products", array("*"));
```

* **Example 2 (read specific columns with conditions and pagination):**

```php
$user_orders = $ordDb->read("orders", array("id", "product_id", "created_at"), array("user_id" => $user_id), 10, 20); // Retrieve 10 orders starting from the 21st record for the user.
```

**6. update($table, $cols, $conditions):**

This method updates existing rows in a table based on conditions.

* **Arguments:**
    * `$table`: (String) Name of the table to update.
    * `$cols`: (Associative Array) An array containing key-value pairs for the data to be updated.
    * `$conditions` (Optional): (Associative Array) An array containing key-value pairs for filtering the rows to update.

* **Example:**

```php
$ordDb->update("users", array("name" => "John Doe"), array("id" => $user_id));
```

**7. drop($table, $conditions):**

This method deletes rows from a table based on conditions.

* **Arguments:**
    * `$table`: (String) Name of the table to delete from.
    * `$conditions` (Optional): (Associative Array) An array containing key-value pairs for filtering the rows to delete.

* **Example:**

```php
$ordDb->drop("cart", array("user_id" => $_SESSION["id"])); // Remove all items from the user's cart.
```

**8. login($user, $password):**

This method handles user login logic (implementation not provided). It likely checks credentials and returns a session number upon successful login.

* **Arguments:**
    * `$user`: (String) Username for login.
    * `$password`: (String) User's password.

**9. logout():**

This method destroys the user session (implementation not provided).

**Important Note:**

* Remember to escape user input before using it in conditions to prevent SQL injection vulnerabilities.
* This documentation provides basic examples. Refer to the actual code for complete functionalities of each method.

## Client usage

### Login

The client should send a POST request with a JSON body containing at least one key named operation to specify the desired action.
The request can include additional parameters specific to the chosen operation.

Before performing any other operations, users must first authenticate themselves using the following login query:

```bash
curl -X POST -H "Content-Type: application/json" -d '{"user": "mayas", "password": "123"}' http://localhost/orm/orm-login.php
```

If the credentials are invalid, the API response will be one of the following:

```json
{"status":"failed","value":"sa","msg":"user is not activated"}
```

```json
{"status":"failed","value":"123","msg":"password error"}
```

```json
{"status":"failed","value":"sa","msg":"user not exists"}
```

and if the credentials are valid:

```json
{"status":"success","value":"poiugpdsvs882fmdtp5alkd927","msg":"1"}
```

Upon successful login, the response will contain a session ID. This value should be stored in a cookie named PHPSESSID on the user's browser.

### Execute commands

This is an example of command:

```bash
curl -X POST -H "Content-Type: application/json" -d '{operation: "read-stores", length: "10", offset: 0, search: null}' http://localhost/orm/admin-api.php
```

Without sending the PHPSESSID value from your client-side code, you might experience:

```json
{"status":"error","msg":"<unknown user> doesn't has access permissions."}
```

otherwise:

```json
[
    {
        "id": "1",
        "name": "main store",
        "logo": "https:\/\/main-store.com\/backend\/images\/stores\/Logo1.png",
        "province": "Damascus",
        "address": "Jaramana",
        "user": "09911223344",
        "comment": "test store",
        "created_at": "2023-03-12 11:25:11",
        "updated_at": null,
        "price_round": null,
        "is_disabled": "0",
        "is_city_enabled": "1"
    }
]
```
