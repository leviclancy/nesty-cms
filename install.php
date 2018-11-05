<pre>

<h1>Setting up database schema</h1>

<? include_once('config.php');

echo "test1";

// make connection without database
$connection_pdo = new PDO("mysql:host=$server;charset=utf8mb4", $username, $password);

include_once('functions.php');

echo "test2";

// create database
$sql_temp = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating database", "full");

echo "test3";

// make connection with database now that it certainly exists
$connection_pdo = new PDO("mysql:host=$server;dbname=$database;charset=utf8mb4", $username, $password);

// create users table
$table_temp = [
	"`user_id` VARCHAR(100)",
	"`status` VARCHAR(100)",
	"`email` VARCHAR(100)",
	"`name` VARCHAR(100)",
//	"`magic_code` VARCHAR(50)",
//	"`magic_time` VARCHAR(50)",
	"`cookie_code` VARCHAR(400)",
	"`cookie_time` VARCHAR(50)",
	"`reset_code` VARCHAR(400)",
	"`reset_time` VARCHAR(50)",
	"`hash` VARCHAR(400)",
	"`authenticator` VARCHAR(100)",
	"`cookie` VARCHAR(100)" ];

// temp
// temp
// temp
$sql_temp = "ALTER TABLE $database.users ADD COLUMN `reset_code` VARCHAR(400) AFTER `cookie_time`;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating reset_code column", "full");

// temp
// temp
// temp
$sql_temp = "ALTER TABLE $database.users ADD COLUMN `reset_time` VARCHAR(50) AFTER `reset_code`;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating reset_time column", "full");

$sql_temp = "CREATE TABLE IF NOT EXISTS $database.users (".implode(', ', $table_temp).",  timestamp TIMESTAMP, PRIMARY KEY (`user_id`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating users table", "full");

if (!(empty($_POST['submit']))):
	if (empty($_POST['email']) || empty($_POST['password1'])):
		echo "<br><b>user information incomplete</b><br>";
	elseif ($_POST['password1'] == $_POST['password2']):
		$values_temp = [
			"user_id"=>random_code(10),
			"status"=>"admin",
			"email"=>$_POST['email'],
			"hash"=>sha1($_POST['email'].$_POST['password1']),
			"authenticator"=>random_code(16) ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$run_statement = $connection_pdo->prepare($sql_temp);
		$run_statement->execute($values_temp);
		execute_checkup($run_statement->errorInfo(), "<br><b>created account login</b><br>");
	else:
		echo "<br><b>passwords did not match</b><br>";
		endif;
	endif;
                 
// create media table
$sql_temp = "CREATE TABLE IF NOT EXISTS $database.media (`media_id` VARCHAR(100), `directory` VARCHAR(100), `filename_original` VARCHAR(200),`filename_full` VARCHAR(200), `filename_large` VARCHAR(200), `filename_thumb` VARCHAR(200), `datetime_original` DATETIME, `datetime_file` DATETIME, `datetime_process` DATETIME, `model` VARCHAR(100), `exposure` VARCHAR(100), `fnumber` VARCHAR(100), `iso` VARCHAR(100), `focallength` VARCHAR(100), `description` TEXT, timestamp TIMESTAMP, PRIMARY KEY (`media_id`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating media table", "full");

// create pages table
$sql_temp = "CREATE TABLE IF NOT EXISTS $database.pages (`page_id` VARCHAR(100), `header` VARCHAR(200), `slug` VARCHAR(200), `cover` VARCHAR(200), `password` VARCHAR(100), `body` LONGTEXT, `created_time` DATE, `updated_time` DATE, `popover` TEXT, timestamp TIMESTAMP, PRIMARY KEY (`page_id`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating pages table", "full");

// create snippets table
$sql_temp = "CREATE TABLE IF NOT EXISTS $database.snippets (`snippet_id` VARCHAR(100), `name` VARCHAR(100), `body` LONGTEXT, `created_time` DATE, `updated_time` DATE, `year` INT4, `month` INT4, `day` INT4, timestamp TIMESTAMP, PRIMARY KEY (`snippet_id`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating snippets table", "full");

// create paths table
$sql_temp = "CREATE TABLE IF NOT EXISTS $database.paths (`path_id` VARCHAR(100), `parent_id` VARCHAR(100), `child_id` VARCHAR(100), timestamp TIMESTAMP, PRIMARY KEY (`path_id`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating paths table", "full");
			
// create site info table
$sql_temp = "CREATE TABLE IF NOT EXISTS $database.siteinfo (`key` VARCHAR(100), `value` VARCHAR(900), timestamp TIMESTAMP, PRIMARY KEY (`key`)) DEFAULT CHARSET=utf8mb4;";
$run_statement = $connection_pdo->prepare($sql_temp);
$run_statement->execute();
$result = execute_checkup($run_statement->errorInfo(), "creating siteinfo table", "full");
				     
// select users from table and if it is empty then create a user
$sql_temp = "SELECT * FROM $database.users";
$login = 0;
foreach ($connection_pdo->query($sql_temp) as $row):
	$login = 1;
	endforeach;

if ($login == 0):
	echo "<hr>";
	echo "<h1>Create login</h1>";
	echo "<form action='' method='post'>";
	echo "<input type='email' name='email' placeholder='email' required><br>";
	echo "<input type='password' name='password1' placeholder='password' required><br>";
	echo "<input type='password' name='password2' placeholder='password' required><br>";
	echo "<input type='submit' name='submit' value='create'>";
	echo "</form>";
	exit; endif; ?>

<hr>

<h1>Configure Apache</h1>

1) Run this command: <i>sudo a2enmod rewrite</i>
2) Locate your Apache config file, usually in /etc/apache2/sites-available/
3) Update your Apache config file by adding the follow chunk at the bottom outside virtualhosts,
<i><Directory /var/www/[DIRECTORY]/>
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</Directory></i>
4) Run this command to restart Apache: <i>sudo service apache2 restart</i>

</pre>
