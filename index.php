<? session_start();
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
include_once('config.php');
$connection_pdo = new PDO("mysql:host=$server;dbname=$database;charset=utf8mb4", $username, $password);

include_once('functions.php');

$page_temp = $slug_temp = $command_temp = null;
$login = $page = $action = $page_confirmed = null;

if (isset($redirect_array[$page_temp])): 
	permanent_redirect($redirect_array[$page_temp]);
	endif;

$publisher = $color = $description = null;
$recaptcha_site = $recaptcha_private = $google_analytics_code = $google_authenticator_code = null;
foreach ($connection_pdo->query("SELECT * FROM $database.siteinfo") as $row):	
	if ($row['key'] == "publisher"): $publisher = $row['value']; endif;
	if ($row['key'] == "recaptcha_site"): $recaptcha_site = $row['value']; endif;
	if ($row['key'] == "recaptcha_private"): $recaptcha_private = $row['value']; endif;
	if ($row['key'] == "google_analytics_code"): $google_analytics_code = $row['value']; endif;
	if ($row['key'] == "google_authenticator_toggle"): $google_authenticator_toggle = $row['value']; endif;
	if ($row['key'] == "color"): $color = $row['value']; endif;
	if ($row['key'] == "description"): $description = $row['value']; endif;
	endforeach;

$page_temp = $slug_temp = $command_temp = null;
$url_temp = explode("/",$_SERVER['REQUEST_URI']);
if (!(empty($url_temp['1']))): $page_temp = $url_temp['1']; endif;
if (!(empty($url_temp['2']))): $slug_temp = $url_temp['2']; endif;
if (!(empty($url_temp['3']))): $command_temp = $url_temp['3']; endif;

if ($page_temp == "api"):
	if ($slug_temp == "sitemap"): include_once('api_sitemap.php'); endif;
	exit; endif;

if ($page_temp == "sitemap.xml"):
	$url_temp = "/sitemap.xml";
	if ($_SERVER['REQUEST_URI'] !== $url_temp): permanent_redirect("https://".$domain.$url_temp); endif;
	$result_temp = file_get_contents("https://".$domain."/api/sitemap/?order=english");
	$information_array = json_decode($result_temp, true);
	echo "<?xml version='1.0' encoding='UTF-8'?>";
	echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";
	foreach ($information_array as $entry_id => $entry_info):
		echo "<url><loc>https://".$domain."/".$entry_id."/</loc>";
		echo "</url>";
		endforeach;
	echo "</urlset>";
	exit; endif;

// if the page is set to log out then logout
if ($page_temp == "logout"):
	setcookie("cookie_code", null, time()-1000, '/');
	permanent_redirect("https://".$domain);
	endif;

if ((strpos("*".$page_temp, "?setup=") == 1) && empty($slug_temp) && !(empty($_REQUEST['setup']))):

	// log out the user, invalidate all cookies

	$setup_code_validity = 0;

	// check if the code and time are valid by doing a quick SQL check on it

	 // if not valid then display an error with a nice empty state
	if ($setup_code_validity !== 1):
		// amp_header
		// tell them that code is no longer valid sadly
		footer(); endif;
	
	// if the setup code is valid
	if ($setup_code_validity == 1):
		include_once('admin_setup.php');
		footer(); endif;

	endif;

$login_hash = $new_cookie = $login = null;
if (!(empty($_POST['checkpoint_email'])) && !(empty($_POST['checkpoint_password']))):
	$_POST['checkpoint_email'] = strtolower($_POST['checkpoint_email']);
	$login_hash = sha1($_POST['checkpoint_email'].$_POST['checkpoint_password']);
	if (!(empty($recaptcha_site)) && !(empty($recaptcha_private)) && ($recaptcha_override !== "yes")):
		$post_temp = [ "secret" => $recaptcha_private, "response" => $_POST['g-recaptcha-response'], "remoteip"=> $_SERVER['REMOTE_ADDR']];
		$opts = [ "http" => [ "method" => "POST", "header" => "Content-type: application/x-www-form-urlencoded", "content" => http_build_query($post_temp) ] ];
		$recaptcha_result = file_get_contents("https://www.google.com/recaptcha/api/siteverify", false, stream_context_create($opts));
		$recaptcha_result = json_decode($recaptcha_result, true);
		if ((int)$recaptcha_result['success'] !== 1):
			$login_hash = $_COOKIE['cookie_code'] = null;
			setcookie("cookie_code", null, time()-1000, '/');
			permanent_redirect("https://".$domain."/account/"); endif;
		endif;
	endif;

// if there is a reset then check it
if ( ($page_temp == "account") && !(empty($_REQUEST['reset']))):
	// check if the reset is within the last five minutes and is valid
	if ($test == "yes this is it"):
		include_once('admin_account_confirm.php');
		endif;
	permanent_redirect("https://".$domain);
	endif;

// if there is a cookie then double-check it
$users_list = []; $admin_count = 0;
foreach ($connection_pdo->query("SELECT * FROM $database.users ORDER BY status ASC, email ASC") as $row):

	$users_list[$row['user_id']] = [
		"user_id" => $row['user_id'],
		"email" => $row['email'],
		"name" => $row['name'],
		"status" => $row['status'] ];

print_r($row);

exit;

	if ($row['status'] == "admin"): $admin_count++; endif;

	if (!(in_array($row['status'], ["admin", "contributor"]))): continue; endif;

	if (!(empty($login))): continue; endif;

	if (!(in_array($row['status'], ["admin", "contributor"]))): continue; endif;

	// create new login
	if (!(empty($login_hash)) && ($row['hash'] == $login_hash)):

		if ($google_authenticator_toggle == "on"):
			if (ctype_space($_POST['checkpoint_authenticator'])): continue; endif;
			$_POST['checkpoint_authenticator'] = str_replace([" ", "-"], null, $_POST['checkpoint_authenticator']);
			if ($_POST['checkpoint_authenticator'] !== code_generator($row['authenticator'])): continue; endif;
			endif;
		$new_cookie = sha1($row['user_id'].time());
		$values_temp = [
			"user_id"=>$row['user_id'],
			"cookie_code"=>$new_cookie,
			"cookie_time"=>time() ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$update_cookie = $connection_pdo->prepare($sql_temp);
		$update_cookie->execute($values_temp);
		$result = execute_checkup($update_cookie->errorInfo(), "creating login cookie");
		if ($result == "failure"):
			permanent_redirect("https://".$domain."/account/");
		else:
			$row['cookie_code'] = $_COOKIE['cookie_code'] = $new_cookie; 
			setcookie("cookie_code", $new_cookie, time()+86400, '/');
			endif; 
		endif;

	// check login
	if (!(empty($_COOKIE['cookie_code'])) && ($row['cookie_code'] == $_COOKIE['cookie_code'])):
		$login = $users_list[$row['user_id']];
		$login['cookie_time'] = $row['cookie_time'];
		$login['authenticator'] = $row['authenticator'];
		endif;

	endforeach;


if (in_array($page_temp, ["account", "security", "website", "users", "new", "add"]) && empty($login)):
	setcookie("cookie_code", null, time()-8000, '/');
	if ($_SERVER['REQUEST_URI'] !== "/account/"): permanent_redirect("https://".$domain."/account/"); endif;
	login(); endif;

if (($page_temp == "account") && !(empty($login))):
	include_once('admin_account.php'); endif;

if (($page_temp == "security") && !(empty($login))):
	include_once('admin_security.php'); endif;

if (($page_temp == "website") && !(empty($login))):
	include_once('admin_website.php'); endif;

if (($page_temp == "users") && !(empty($login))):
	include_once('admin_users.php'); endif;


// display search results
if ($page_temp == "search"):
	if (isset($_REQUEST['clear_term'])): $_SESSION['term'] = $_REQUEST['term'] = null; endif;
	if (isset($_REQUEST['clear_date'])): $_SESSION['since'] = $_REQUEST['since'] = $_SESSION['through'] = $_REQUEST['through'] = null; endif;
	if (isset($_REQUEST['term'])): $_SESSION['term'] = $_REQUEST['term']; endif;
	if (isset($_REQUEST['since'])): $_SESSION['since'] = $_REQUEST['since']; endif;
	if (isset($_REQUEST['through'])): $_SESSION['through'] = $_REQUEST['through']; endif;
	if ($slug_temp !== "listing"):
		$proper_uri = "/search/";
		if (is_numeric($slug_temp)): $proper_uri .= $slug_temp."/"; endif;
		if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
		include_once('theme_search.php');
	elseif ($slug_temp == "listing"):
		$proper_uri = "/search/listing/";
		if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
		include_once('theme_search.php'); endif;
	endif;

if ($page_temp == "sitemap"):
	$proper_uri = "/sitemap/";
	if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
	include_once('theme_home_sitemap.php');
	endif;

if ($page_temp == "schedule"):
	$proper_uri = "/schedule/";
	if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
	include_once('theme_home_schedule.php');
	endif;

// options to create
if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($page_temp == "create")):
	include_once('admin_page_create.php');
	endif;

// add new page
if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($page_temp == "new")):
	include_once('admin_page.php');
	endif;

// add new snippet
if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($page_temp == "add")):
	include_once('admin_snippet.php');
	endif;

// edit the page
if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($_POST['page_edit']))):
	include_once('admin_page.php');
	endif;

// edit the snippet
if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($_POST['snippet_edit']))):
	include_once('admin_snippet.php');
	endif;

// unlock or relock the page
if (in_array($page_temp, ["unlock", "relock"]) && !(empty($_POST['page']))):	
	$page_confirmed = nesty_page($_POST['page']);
	if (empty($page_confirmed[$_POST['page']])): notfound(); endif;

	// largely thanks to https://stackoverflow.com/questions/43422257/amp-form-submission-redirect-or-response

	header("Content-type: application/json");
	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Origin: https://".$domain);
	header("AMP-Access-Control-Allow-Source-Origin: https://".$domain);

	// if failure
	// header("HTTP/1.0 412 Precondition Failed", true, 412);
	// and end headers here

	// if no redirect
	// header("Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin");
	// and end headers here

	header("AMP-Redirect-To: https://".$domain."/".$_POST['page']);
	header("Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin");

	if ($page_temp == "unlock"):
		$_SESSION[$_POST['page']] = $_POST['password'];
		echo json_encode(["result"=>"success", "message"=>"Password did not succeed."]);
		endif;
	if ($page_temp == "relock"):
		unset($_SESSION[$_POST['page']]);
		echo json_encode(["result"=>"success", "message"=>"Password cleared."]);
		endif;

	exit; endif;

// check if the page exists and redirect properly
if (!(empty($page_temp)) && !(in_array($page_temp,["m", "s"]))):
	$page_confirmed = nesty_page($page_temp);
	if (empty($page_confirmed[$page_temp])): notfound(); endif; endif;

// deleting a page
if (!(empty($page_confirmed[$page_temp])) && !(empty($_POST['delete_page']))):
	// delete from pages table
	$delete_statement = $connection_pdo->prepare("DELETE FROM $database.pages WHERE page_id=:page_id");
	$delete_statement->execute(["page_id"=>$_POST['delete_page']]);
	$result = execute_checkup($delete_statement->errorInfo(), "deleting page");

	// delete from paths table
	$delete_statement = $connection_pdo->prepare("DELETE FROM $database.paths WHERE parent_id=:page_id or child_id=:page_id");
	$delete_statement->execute(["page_id"=>$_POST['delete_page']]);
	$result = execute_checkup($delete_statement->errorInfo(), "deleting paths with page");

	permanent_redirect("https://$domain/"); endif;


// render the page for public consumption
if (!(empty($page_confirmed[$page_temp]))):
	
	$proper_uri = "/".$page_temp."/";
	if (!(empty($page_confirmed[$page_temp]['slug']))): $proper_uri .= $page_confirmed[$page_temp]['slug']."/"; endif;

	if ($slug_temp == "*"):
		unset($_SESSION[$page_temp]);
		endif;

	if (($command_temp == "edit") && !(empty($login)) && in_array($login['status'], ["contributor", "admin"])):
		permanent_redirect("https://$domain/".$page_temp."/edit/"); 
		endif;

	if (($slug_temp == "edit") && in_array($login['status'], ["contributor", "admin"]) && !(empty($login))):
		include_once('admin_page.php');
		endif;

	if (in_array("delete", [$slug_temp, $command_temp]) && !(empty($login))):
		html_header($page_confirmed[$page_temp]['header'], $domain."/delete/");
		echo "<form action='' method='post'>";
		echo "<div id='delete-window-header'>".$page_confirmed[$page_temp]['header']."</div>";
		echo "<div id='delete-window-content-id'>".$page_temp."</div>";
		echo "<a href='https://".$domain."/".$page_temp."/edit/'><div id='delete-window-back-button' class='background_2'>Go back</div></a>";
		echo "<button type='submit' name='delete_page' value='".$page_temp."' id='delete-window-delete-button'>Delete page</button>";
		echo "</form>";
		footer(); endif;

	if (($slug_temp == "ping") || ($command_temp == "ping")):
		echo json_encode($page_confirmed); exit; endif;
	if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;

	include_once('theme_page.php');
	endif;

// check if the media exists
if ($page_temp == "m"):

	$media_confirmed = nesty_media($slug_temp);
	if (empty($media_confirmed[$slug_temp])): notfound(); endif;

	// if $page_temp is a real picture and $slug_temp is full, large, or thumb then redirect to the image link
	if (in_array($command_temp, ["full", "large", "thumb"])):
		permanent_redirect("https://$domain/media/".$media_confirmed[$slug_temp]['directory']."/".$slug_temp."_".$command_temp.".jpg");
		exit; endif;

	// deliver ping stuff
	if (in_array($command_temp, ["ping"])):
		echo json_encode($media_confirmed);
		exit; endif;
	endif;
    
if ($page_temp == "s"):
	$snippet_confirmed = nesty_snippet($slug_temp);
	if (empty($snippet_confirmed[$slug_temp])): notfound(); endif;

	// if $page_temp is a real picture and $slug_temp is full, large, or thumb then redirect to the image link
	if (in_array($command_temp, ["edit"]) && !(empty($login)) && in_array($login['status'], ["contributor", "admin"])):
		include_once('admin_snippet.php');
		endif;

	// delete the page option

	// deliver ping stuff
	if (in_array($command_temp, ["ping"])):
		$snippet_confirmed[$slug_temp]['body'] = body_process($snippet_confirmed[$slug_temp]['body']);
		echo json_encode($snippet_confirmed);
		exit; endif;
	
	echo "this snippet exists";

	exit; endif;

// if $page_temp is a real picture then show picture info
if (!(empty($media_confirmed[$slug_temp]))):
	if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && ($command_temp == "edit")):
		$proper_uri = "/m/".$slug_temp."/edit/";
		if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
		include_once('admin_image.php'); endif;

	if (!(empty($login)) && ($command_temp == "delete") && ( empty($_POST['delete_image']) || ($_POST['delete_image'] !== $slug_temp)) ):
		html_header($slug_temp, $domain."/delete/");
		echo "<img src='https://".$domain."/m/".$slug_temp."/thumb/' id='delete-window-thumbnail'>";
		echo "<form action='' method='post'>";
		echo "<div id='delete-window-content-id'>".$slug_temp."</div>";
		echo "<a href='https://".$domain."/m/".$slug_temp."/edit/'><div id='delete-window-back-button' class='background_2'>Go back</div></a>";
		echo "<button type='submit' name='delete_image' value='".$slug_temp."' id='delete-window-delete-button'>Delete image</button>";
		echo "</form>";
		footer(); endif;

	// go on and delete it
	if (!(empty($login)) && ($command_temp == "delete") && !(empty($_POST['delete_image'])) && ($_POST['delete_image'] == $slug_temp)):

		// delete from media table
		$delete_statement = $connection_pdo->prepare("DELETE FROM $database.media WHERE media_id=:media_id");
		$delete_statement->execute(["media_id"=>$_POST['delete_image']]);
		$result = execute_checkup($delete_statement->errorInfo(), "deleting media");

		// delete from paths table
		$delete_statement = $connection_pdo->prepare("DELETE FROM $database.paths WHERE parent_id=:media_id or child_id=:media_id");
		$delete_statement->execute(["media_id"=>$_POST['delete_image']]);
		$result = execute_checkup($delete_statement->errorInfo(), "deleting paths with media");

		unlink("media/".$media_confirmed[$slug_temp]['directory']."/".$slug_temp."_full.jpg");		
		unlink("media/".$media_confirmed[$slug_temp]['directory']."/".$slug_temp."_large.jpg");		
		unlink("media/".$media_confirmed[$slug_temp]['directory']."/".$slug_temp."_thumb.jpg");		

		permanent_redirect("https://$domain");
		endif;

	$proper_uri = "/m/".$slug_temp."/";
	if ($_SERVER['REQUEST_URI'] !== $proper_uri): permanent_redirect("https://$domain".$proper_uri); endif;
	include_once('theme_image.php');
	endif;

$_SESSION['term'] = $_SESSION['since'] = $_SESSION['through'] = null;

include_once('theme_home.php'); ?>
