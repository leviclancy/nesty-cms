<? html_header($page_temp, $domain." ".$page_temp);

echo "<style> /* The switch - the box around the slider */
.switch {
  position: relative;
  display: block;
  margin: 0 auto;
  width: 60px;
  height: 34px;
}

/* Hide default HTML checkbox */
.switch input {display:none;}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
  border-radius: 34px;
}

.slider:before {
  position: absolute;
  content: '';
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: ".$color.";
}

input:focus + .slider {
  box-shadow: 0 0 1px ".$color.";
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
} </style>";

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

$result_success = 0;

$result_failure = null;

echo "<div id='admin-window'>";

// update site settings and security
if (!(empty($_POST['update'])) && ($login['status'] == "admin")):

	$values_temp = [
		"key"=>null,
		"value"=>null ];
	$sql_temp = sql_setup($values_temp, "$database.siteinfo");
	$update_siteinfo = $connection_pdo->prepare($sql_temp);

	// update basic site info
	if ($_POST['update'] == "settings"):
		if ($_POST['publisher'] !== $publisher):
			$update_siteinfo->execute(["key"=>"publisher", "value"=>trim($_POST['publisher'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating publisher okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		if ($_POST['description'] !== $description):
			$update_siteinfo->execute(["key"=>"description", "value"=>trim($_POST['description'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating description okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		if ($_POST['google_analytics_code'] !== $google_analytics_code):
			$update_siteinfo->execute(["key"=>"google_analytics_code", "value"=>trim($_POST['google_analytics_code'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating google_analytics_code okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		if ($_POST['color'] !== $color):
			$update_siteinfo->execute(["key"=>"color", "value"=>trim($_POST['color'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating color okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		endif;

	// update recaptcha
	if ($_POST['update'] == "recaptcha"):
		if ($_POST['recaptcha_site'] !== $recaptcha_site):
			$update_siteinfo->execute(["key"=>"recaptcha_site", "value"=>trim($_POST['recaptcha_site'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating recaptcha_site okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		if ($_POST['recaptcha_private'] !== $recaptcha_private):
			$update_siteinfo->execute(["key"=>"recaptcha_private", "value"=>trim($_POST['recaptcha_private'])]);
			$result = execute_checkup($update_siteinfo->errorInfo(), "updating recaptcha_private okay");
			if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif; endif;
		endif;

	// update two-factor authentication
	if (in_array($_POST['twofactor'], ["google_authenticator_on", "google_authenticator_off"])):
		$value_temp = "on"; if ($_POST['update'] == "google_authenticator_off"): $value_temp = "off"; endif;
		$update_siteinfo->execute(["key"=>"google_authenticator_toggle", "value"=>$value_temp]);
		$result = execute_checkup($update_siteinfo->errorInfo(), "updating google_authenticator_toggle okay");
		if ($result !== "success"): $result_failure = "failure"; else: $result_success = 1; endif;
		endif;

	endif;

if (!(empty($_POST['update'])) && ($_POST['update'] == "check_authenticator")):
	if ($_POST[$login['user_id']]['check_authenticator'] == code_generator($login['authenticator'])):
		echo "<p style='color: olive; margin: 20px auto; text-align: center; font-style: italic; font-weight: 700;'>";
		echo "six-digit authenticator code succeeded</p>";
	else:
		echo "<p style='color: crimson; margin: 20px auto; text-align: center; font-style: italic; font-weight: 700;'>";
		echo "six-digit authenticator code failed</p>";
		endif;
	endif;

if ($result_success == 1): echo '<script> window.location.replace("https://'.$domain.'/'.$page_temp.'/"); </script>'; endif;

echo "<form action='' method='post'>";

echo "<h2>Website configuration</h2>";

echo "<div class='input-description'>Website name</div>";
echo "<input type='text' name='publisher' value='".htmlspecialchars($publisher)."' placeholder='Website name'><br>";

echo "<div class='input-description'>Google Analytics code (UA-*******-*)</div>";
echo "<input type='text' name='google_analytics_code' value='".htmlspecialchars($google_analytics_code)."' placeholder='Google Analytics code (UA-*******-*)'><br>";

echo "<div class='input-description'>Website theme color</div>";
echo "<input type='color' name='color' value='".htmlspecialchars($color)."' placeholder='background colour'><br>";

echo "<h2>Homepage</h2>";

echo "<div class='input-description'>Website description</div>";
echo "<textarea name='description' placeholder='description' style='width: 400px; max-width: 80%; height: 300px;'>".htmlspecialchars($description)."</textarea>";

echo "<h2>reCAPTCHA</h2>";

echo "<p>reCAPTCHA protects against hackers. Set it up <a href='https://www.google.com/recaptcha/admin'>here</a> and enter ".$domain." as your domain to avoid being locked out from your own site because the reCAPTCHA cannot validate.</p>";

echo "<div class='input-description'>reCAPTCHA site key</div>";
echo "<input type='text' name='recaptcha_site' value='".htmlspecialchars($recaptcha_site)."' placeholder='reCAPTCHA site'><br>";

echo "<div class='input-description'>reCAPTCHA private key</div>";
echo "<input type='text' name='recaptcha_private' value='".htmlspecialchars($recaptcha_private)."' placeholder='reCAPTCHA private'><br>";

$phrase_temp = "active"; $checked_temp = "checked"; 
if ($google_authenticator_toggle == "off"): $phrase_temp = "off"; $checked_temp = null; endif;

echo "<h2>Two-Factor Authentication</h2>";

echo "<input type='hidden' name='twofactor' value='google_authenticator_off'>";
echo "<label class='switch' style='margin:0 30px 15px;'><input type='checkbox' name='twofactor' value='google_authenticator_on' ".$checked_temp."><span class='slider'></span></label>";

echo "<p><b>Two-factor authenticiation is currently ".$phrase_temp.".</b> ";
if ($google_authenticator_toggle !== "off"):
	echo "Make sure to set up your account <a href='/two-factor'>here</a>. ";
	echo "Users who have not set it up yet will need to reset their passwords when they log in again.";
	endif;
echo "</p>";

echo "</div>";

echo "<button type='submit' name='update' value='website' class='floating-action-button'>save</button>";

echo "</form>";

footer(); ?>
