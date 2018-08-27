<? if (empty($_SESSION['salt'])): $_SESSION['salt'] = random_code(10); endif;

html_header($page_temp, $domain." ".$page_temp);

echo "<div id='admin-window'>";

// check if there is a method set, and if there is then go from there
if (!(empty($_REQUEST['method']))):

	// check the method
	$method_user_id = $method_status = null;
	foreach($users_list as $user_id => $user_info):

		if (empty($user_id)): continue; endif;

		// make them an admin: only a contributor can become an admin
		if ($_REQUEST['method'] == encode_thirtytwo("user_id=".$user_id."&status=admin&salt=".$_SESSION['salt'])):
			if ($user_info['status'] !== "contributor"): break; endif;
			$method_user_id = $user_id; $method_status = "admin"; break;

		// make them a contributor: only an admin can become a contributor and there must be at least one admin
		elseif ($_REQUEST['method'] == encode_thirtytwo("user_id=".$user_id."&status=contributor&salt=".$_SESSION['salt'])):
			if (!($admin_count > 1) && ($user_info['status'] == "admin")): break; endif;
			if ($user_info['status'] !== "admin"): break; endif;
			$method_user_id = $user_id; $method_status = "contributor"; break;

		// enable them: no restriction, this puts them in unconfirmed status, just avoid unconfirming last remaining admin
		elseif ($_REQUEST['method'] == encode_thirtytwo("user_id=".$user_id."&status=enable&salt=".$_SESSION['salt'])):
			// if ($user_info['status'] !== "disabled"): break; endif;
			if (!($admin_count > 1) && ($user_info['status'] == "admin")): break; endif;
			$method_user_id = $user_id; $method_status = "unconfirmed"; break;

		// disable them: so long as there is one admin, anyone can be disabled
		elseif ($_REQUEST['method'] == encode_thirtytwo("user_id=".$user_id."&status=disabled&salt=".$_SESSION['salt'])):
			if (!($admin_count > 1) && ($user_info['status'] == "admin")): break; endif;
			$method_user_id = $user_id; $method_status = "disabled"; break;

		// get a setup link
		elseif ($_REQUEST['method'] == encode_thirtytwo("user_id=".$user_id."&status=setup&salt=".$_SESSION['salt'])):
			$method_user_id = $user_id; $method_status = "setup"; break;
			endif;

		endforeach;

	if ( ($login['status'] !== "admin") && ( ($method_user_id !== $login['user_id']) || ($method_status !== "disabled") ) ):
		echo "<p style='font-style: italic; color: crimson;'>Contributors can only make limited changes.</p>";
		endif;

	if (empty($method_user_id)):
		echo "<p style='font-style: italic; color: crimson;'>Attempted action was not valid.</p>";
		endif;

	if (!(empty($method_user_id)) && in_array($method_status, ["admin", "contributor", "disabled", "unconfirmed"])):

		$_SESSION['salt'] = random_code(10);

		$values_temp = [
			"user_id"=>$method_user_id,
			"status"=>$method_status, ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$update_status = $connection_pdo->prepare($sql_temp);
		$update_status->execute($values_temp);
		$result = execute_checkup($update_status->errorInfo(), "updating user status");
		if ($result == "failure"):
			echo "<p syle='font-style: italic; color: crimson'>User status was not changed.</p>";
		else:	
			$users_list[$method_user_id]['status'] = $method_status;
			if ($method_user_id == $login['user_id']):
				$login['status'] = $method_status;
				endif;
			endif;
		endif;

	if (!(empty($method_user_id)) && ($method_status == "setup")):

		$_SESSION['salt'] = random_code(10);

		$setup_code = random_code(32);
		$values_temp = [
			"user_id"=>$method_user_id,
			"status"=>"unconfirmed",
			"reset_code"=>$setup_code,
			"reset_time"=>time() + 600, ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$update_setup_code = $connection_pdo->prepare($sql_temp);
		$update_setup_code->execute($values_temp);
		$result = execute_checkup($update_setup_code->errorInfo(), "updating setup code");
		if ($result == "failure"):
			echo "<p syle='font-style: italic; color: crimson'>User setup link was not created.</p>";
		else:
			$users_list[$method_user_id]['status'] = "unconfirmed";
			$users_list[$method_user_id]['reset_code'] = $setup_code;
			if ($method_user_id == $login['user_id']):
				$login['status'] = "unconfirmed";
				$login['reset_code'] = $reset_code;
				endif;
			endif;
		endif;

	$slug_temp = null;

	endif;

if (!(empty($_POST['add_email']))):
	$add_email = 1;
	foreach ($users_list as $user_id => $user_info):
		if (trim(strtolower($_POST['add_email'])) == trim(strtolower($user_info['email']))):
			$add_email = 0;
			echo "<p style='font-style: italic; color: crimson;'>This e-mail address already exists.</p>";
			break;
			endif;
		endforeach;
	if ($add_email == 1):
		$values_temp = [
			"user_id"=>random_code(5),
			"email"=>$_POST['add_email'],
			"status"=>"unconfirmed", ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$add_user = $connection_pdo->prepare($sql_temp);
		$add_user->execute($values_temp);
		$result = execute_checkup($add_user->errorInfo(), "adding new user");
		if ($result == "failure"):
			echo "<p style='font-style: italic; color: crimson;'>New user was not added.</p>";
			$slug_temp = "create";
		else:
			$users_list = array_merge([$values_temp['user_id'] => $values_temp], $users_list);
			$page_temp = "users"; $slug_temp = null;
			endif;
		endif;
	endif;

$url_temp = $page_temp."/"; if (!(empty($slug_temp))): $url_temp .= $slug_temp."/"; endif;
if ($_SERVER['REQUEST_URI'] !== $url_temp): echo '<script type="text/javascript"> history.replaceState(null, null, "/'.$url_temp.'"); </script>'; endif;

if ($slug_temp == "create"):
	echo "<form action='' method='post'>";
	echo "<div class='input-description'>Add new user by e-mail address</div>";
	echo "<input type='email' name='add_email' placeholder='Add new user (e-mail address)' required><br>";
	echo "<button type='submit' name='add_user' value='add_user' class='background_1'>Create user</button>";
	echo "</form>";
	footer();
	endif;

if ($login['status'] == "admin"):
			
	if (!(empty($setup_code))):
		echo "<p>You generated a setup link for ".$users_list[$method_user_id]['email'].": <br>";
		echo "<kbd>https://".$domain."/?setup=".$setup_code."</kbd></p>";
		endif;
				
	echo "<p>Generate an account setup link and give it to the user so that they may confirm their identity. The link will be valid for five minutes. ";
	echo "If the user was previously confirmed, then this will reset their password and remove their permissions. ";
	echo "Disabling and then re-enabling a user will require them to confirm their identity again.</p>";
	endif;

// add option to delete users if they have no credited posts

echo "<table>";
echo "<thead><tr><th>E-mail</th><th>Role</th><th>Change role</th><th>Change status</th>";
if ($login['status'] == "admin"): echo "<th>Setup link</th>"; endif;
echo "</tr></thead>";
echo "<tbody>";
foreach ($users_list as $user_id => $user_info):

	if (empty($user_id)): continue; endif;

	echo "<tr><td>".$user_info['email']."</td>";

	if ($login['status'] !== "admin"):
		echo "<td>".ucfirst($user_info['status'])."</td>";
		echo "<td>Cannot change</td>";
		echo "<a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=disabled&salt=".$_SESSION['salt'])."'>Disable account</a>";
	elseif (($admin_count > 1) && ($user_info['status'] == "admin")):
		echo "<td>".ucfirst($user_info['status'])."</td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=contributor&salt=".$_SESSION['salt'])."'>Make contributor</a></td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=disabled&salt=".$_SESSION['salt'])."'>Disable account</a></td>";
	elseif (!($admin_count > 1) && ($user_info['status'] == "admin")):
		echo "<td>".ucfirst($user_info['status'])."</td>";
		echo "<td colspan='3'><i>Changes not allowed. There must be at least one admin enabled.</i></td>";
	elseif ($user_info['status'] == "contributor"):
		echo "<td>".ucfirst($user_info['status'])."</td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=admin&salt=".$_SESSION['salt'])."'>Make admin</a></td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=disabled&salt=".$_SESSION['salt'])."'>Disable account</a></td>";
	elseif ($user_info['status'] == "disabled"):
		echo "<td colspan='2'>".ucfirst($user_info['status'])."</td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=enable&salt=".$_SESSION['salt'])."'>Enable account</a></td>";
	else:
		echo "<td colspan='2'>".ucfirst($user_info['status'])."</td>";
		echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=disabled&salt=".$_SESSION['salt'])."'>Disable account</a></td>";
		endif;

	if ($login['status'] == "admin"):
		if (($login['user_id'] !== $user_id) && ($user_info['status'] !== "disabled")):
			echo "<td><a href='/".$page_temp."/?method=".encode_thirtytwo("user_id=".$user_id."&status=setup&salt=".$_SESSION['salt'])."'>Generate setup link</a></td></tr>";
		elseif (($admin_count > 1) && ($user_info['status'] == "admin")):
			echo null;
		else:
			echo "<td></td>";
			endif;
		endif;

    	endforeach;

echo "</tbody></table>";

echo "</div>";

if ($login['status'] == "admin"):
	echo "<a href='/users/create/'><div class='floating-action-button'>add</div></a>";
	endif;

footer(); ?>
