<? html_header($page_temp, $domain." ".$page_temp);

// check install.php output as success or failure

if (empty($login['authenticator']) && ($page_temp == "account")):
	// create code
	$values_temp = [
		"user_id"=>$login['user_id'],
		"authenticator"=>random_code(20) ];
	$sql_temp = sql_setup($values_temp, "$database.users");
	$update_authenticator = $connection_pdo->prepare($sql_temp);
	$update_authenticator->execute($values_temp);
	$result = execute_checkup($update_authenticator->errorInfo(), "updating authenticator okay");
	if ($result == "success"): echo '<script> window.location.replace("https://'.$domain.'/account/"); </script>'; endif;
	endif;

if (!(empty($_POST['update']))):
	$login_hash = sha1($login['email'].$_POST[$login['user_id']]['password_current']);
	$password_check = 0;
	foreach ($connection_pdo->query("SELECT * FROM $database.users") as $row):
		if ($row['user_id'] !== $login['user_id']): continue; endif;
		if ($row['hash'] == $login_hash): $password_check = 1; break; endif;
		endforeach;
	if ($password_check !== 1):
		echo "<p style='font-style: italic; color: crimson;'>Current password did not validate.</p>";
		$_POST[$login['user_id']] = [];
		endif;
	endif;

if (!(empty($_POST[$login['user_id']]['name'])) && ($_POST[$login['user_id']]['name'] !== $login['name'])):
	$values_temp = [
		"user_id"=>$login['user_id'],
		"name"=>trim($_POST[$login['user_id']]['name']) ];
	$sql_temp = sql_setup($values_temp, "$database.users");
	$update_userinfo = $connection_pdo->prepare($sql_temp);
	$update_userinfo->execute($values_temp);
	$result = execute_checkup($update_userinfo->errorInfo(), "updating your name", "full");
	if ($result == "success"): $login['name'] = $values_temp['name']; endif;
	endif;

if (!(empty($_POST[$login['user_id']]['email'])) && ($_POST[$login['user_id']]['email'] !== $login['email'])):
	$existing_temp = 0;
	foreach ($users_list as $user_info):
		if ($user_info['email'] == $_POST[$login['user_id']]['email']): $exiting_temp = 1; break; endif;
		endforeach;
	if ($existing_temp == 1):
		echo "<p style='font-style: italic; color: crimson;'>E-mail address is already in use.<br><br></p>"; endif;
	if ($existing_temp == 0):
		$values_temp = [
			"user_id"=>$login['user_id'],
			"email"=>trim($_POST[$login['user_id']]['email']) ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$update_userinfo = $connection_pdo->prepare($sql_temp);
		$update_userinfo->execute($values_temp);
		$result = execute_checkup($update_userinfo->errorInfo(), "updating your email", "full");
		if ($result == "success"): $login['email'] = trim($_POST[$login['user_id']]['email']); endif;
		endif;
	endif;

echo "<div id='admin-window'>";

echo "<form action='' method='post'>";

echo "<h2>Your information</h2>";

echo "<div class='input-description'>Your email address (e.g. name@example.com)</div>";
echo "<input type='email' name='".$login['user_id']."[email]' value='".htmlspecialchars($login['email'])."' placeholder='Your email address' required><br>";

echo "<div class='input-description'>Your name (e.g. Sarah Eretz)</div>";
echo "<input type='text' name='".$login['user_id']."[name]' value='".htmlspecialchars($login['name'])."' placeholder='Your name' required><br>";

echo "<div class='input-description'>Current password (required to make changes)</div>";
echo "<input type='password' name='".$login['user_id']."[password_current]' placeholder='Current password' autocomplete='nope' required><br>";

echo "<button type='submit' name='update' value='account' class='background_1'>Update information</button>";

echo "</form>";

echo "</div>";

footer(); ?>
