<? html_header($page_temp, $domain." ".$page_temp);

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

if (!(empty($_POST[$login['user_id']]['update_login_one']))):

	$password_temp = [trim($_POST[$login['user_id']]['update_login_one']), trim($_POST[$login['user_id']]['update_login_two'])];

	if (empty($password_temp[1])):
		echo "<p style='font-style: italic; color: crimson;'>You cannot leave the confirmation field blank for your new password.<br><br></p>";
	elseif ($password_temp[0] !== $password_temp[1]):
		echo "<p style='font-style: italic; color: crimson;'>Passwords did not match, please try again.<br><br></p>";
	else:
		$values_temp = [
			"user_id"=>$login['user_id'],
			"hash"=>sha1(strtolower($login['email']).$password_temp[0]) ];
		$sql_temp = sql_setup($values_temp, "$database.users");
		$update_userinfo = $connection_pdo->prepare($sql_temp);
		$update_userinfo->execute($values_temp);
		$result = execute_checkup($update_userinfo->errorInfo(), "updating your password", "full");
		endif;
	endif;

echo "<div id='admin-window'>";

echo "<h2>Two-factor authentication</h2>";

$shortlink_code = 'otpauth://totp/'.$login['email'].'?secret='.encode_thirtytwo($login['authenticator']).'&issuer='.$domain;

echo "<p>Scan QR code below with Google Authenticator or DUO.";
echo "<br><br><a href='".$shortlink_code."'>Click here to auto-enter.</a>";
echo "<br><br>Tap below to reveal scannable QR code.</p>";

echo '<script>
	$(document).ready(function(){
		$("#qrcode").click(function(){
			if (!$("#qrcode").is(":animated")) {
				$("#qrcode").fadeTo(500,1);
				$("#qrcode").delay(3500).fadeTo(500,0,"swing"); }
			});
		});
	</script>';

echo "<div style='position: relative; display: block; width: 400px; height: 400px; border-radius: 5px; padding: 2px 0 0 0; margin: 0 30px; text-align: center;' class='background_5'>";
echo "<div id='qrcode' style='padding: 0; margin: 0; opacity: 0; z-index: 1000; position: relative; border-radius: 5px;'></div></div>";
echo '<script type="text/javascript">
	var element = document.getElementById("qrcode");
	var bodyElement = document.body;
	element.appendChild(showQRCode("'.$shortlink_code.'"));
	</script>';
	
	echo "<form action='' method='post'>";

echo "<h2>Your password</h2>";

echo "<div class='input-description'>New password</div>";
echo "<input type='password' name='".$login['user_id']."[update_login_one]' placeholder='New password' autocomplete='nope' required><br>";

echo "<div class='input-description'>Confirm new password (enter again)</div>";
echo "<input type='password' name='".$login['user_id']."[update_login_two]' value='' placeholder='Confirm new password (enter again)' autocomplete='nope' required><br>";

echo "<div class='input-description'>Current password (required to make changes)</div>";
echo "<input type='password' name='".$login['user_id']."[password_current]' placeholder='Current password' autocomplete='nope' required><br>";

echo "<button type='submit' name='update' value='account' class='background_1'>Update password</button>";

echo "</form>";

echo "</div>";

footer(); ?>
