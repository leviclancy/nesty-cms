<? // html_header, amp_header, admin_bar, footer, login, notfound

function html_header($title=null, $canonical=null) {
	global $domain;
	global $publisher;
	global $color;
	global $google_analytics_code;
	global $page_temp;
	global $slug_temp;
	global $command_temp;
	if (empty($title)): $title = $domain; endif;

	echo "<!doctype html>" . "<html lang='en'>" . "<head>" . "<meta charset='utf-8'>";

	echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>";
	echo "<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css'>";
	echo "<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'></script>";
	
	// recaptcha js
	if ($page_temp == "account"):
		echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
		endif;
	
	// qr code js
	global $login;
	if (!(empty($login)) && ($page_temp == "security")):
		echo "<script src='https://".$domain."/qrcode.js'></script>";
		echo "<script src='https://".$domain."/html5-qrcode.js'></script>";
		endif;
	
	if (empty($canonical)): $canonical=$domain; endif; // do some sort of url validation here
	echo "<link rel='canonical' href='https://$canonical'>"; // must define canonical url for amp

	echo "<title>" . $title . "</title>";
	echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';

	echo "<style>";
	include_once('style.css');
	include_once('style_nesty.css');
	echo "</style>";

	if (!(empty($google_analytics_code))):
		echo "<script>"; ?>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
			ga('create', '<? echo $google_analytics_code ?>', 'auto');
			ga('send', 'pageview');
		<? echo "</script>"; endif;

	echo "</head><body>";

	global $login;
	$admin_navigation_array = [
		"account" => "Profile",
		"security" => "Security",
		"website" => "Website",
		"users" => "Users", ];
	if (!(empty($login)) && !(empty($admin_navigation_array[$page_temp]))):
		echo "<div id='admin-navigation'>";
		echo "<a href='/'><div id='admin-navigation-home-button'>Home</div></a>";
		echo "<a href='/logout/'><div id='admin-navigation-logout-button'>Log out</div></a>";
		foreach ($admin_navigation_array as $option_backend => $option_pretty):
			if (in_array($option_backend, ["settings", "security"]) && ($login['status'] !== "admin")): continue; endif;
			$class_temp = null; if ($page_temp == $option_backend): $class_temp = "admin-navigation-option-button-selected"; endif;
			echo "<a href='/".$option_backend."/'><div class='admin-navigation-option-button ".$class_temp."'>".$option_pretty."</div></a>";
			endforeach;
		echo "</div>";
		endif; }


function amp_header($title=null, $canonical=null) {
	global $domain;
	global $publisher;
	global $google_analytics_code;
	global $google_ad_client;
	global $color;
	global $page_temp;
	global $slug_temp;
	global $command_temp;
	global $_SESSION;
	if (empty($title)): $title = $domain; endif;

	// https://www.ampproject.org/docs/tutorials/create/basic_markup

	// these must open the document
	echo "<!doctype html>" . "<html amp lang='en'>";

	// open html head
	echo "<head>" . "<meta charset='utf-8'>";

	// for google analytics, this must precede amp js
	if (!(empty($google_analytics_code))):
		echo '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>';
		endif;

	// amp js
	echo "<script async src='https://cdn.ampproject.org/v0.js'></script>";

	if (empty($canonical)): $canonical=$domain; endif; // do some sort of url validation here
	echo "<link rel='canonical' href='https://$canonical'>"; // must define canonical url for amp

	// amp boilerplate code https://www.ampproject.org/docs/reference/spec/amp-boilerplate
	echo "<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>";

	// for amp-form
	echo '<script async custom-element="amp-form" src="https://cdn.ampproject.org/v0/amp-form-0.1.js"></script>';

	// for amp-bind
	echo '<script async custom-element="amp-bind" src="https://cdn.ampproject.org/v0/amp-bind-0.1.js"></script>';
	
	// mostly for show-more features
	echo '<script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script>';
	
	// for lightbox search feature
	echo '<script async custom-element="amp-lightbox" src="https://cdn.ampproject.org/v0/amp-lightbox-0.1.js"></script>';

	// for text fitting on images in particular
	echo '<script async custom-element="amp-fit-text" src="https://cdn.ampproject.org/v0/amp-fit-text-0.1.js"></script>';	
	
	// for the parallax
	echo '<script async custom-element="amp-fx-collection" src="https://cdn.ampproject.org/v0/amp-fx-collection-0.1.js"></script>';

	// for the view more
	echo '<script async custom-element="amp-accordion" src="https://cdn.ampproject.org/v0/amp-accordion-0.1.js"></script>';

	// for the adsense ads
//	if (!(empty($google_ad_client)) && !(empty($page_temp)) && !(in_array($page_temp, ["m", "search"]))):
//		echo '<script async custom-element="amp-auto-ads" src="https://cdn.ampproject.org/v0/amp-auto-ads-0.1.js"></script>';
//		endif;
	
	echo "<title>" . $title . "</title>";
	echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
	
//	echo "<base href='/' />";
	echo "<meta name='viewport' content='width=device-width,minimum-scale=1,initial-scale=1'>"; // must define viewport for amp

	echo "<style amp-custom>";
	include_once('style.css');
	include_once('style_nesty.css');
	echo "</style>";

	echo "</head><body>";
	
	// for the adsense ads
//	if (!(empty($google_ad_client)) && !(empty($page_temp)) && !(in_array($page_temp, ["m", "search"]))):
//		echo '<amp-auto-ads type="adsense" data-ad-client="'.$google_ad_client.'"></amp-auto-ads>';
//		endif;
	
	if (!(empty($google_analytics_code))):
		echo '<amp-analytics type="googleanalytics">';
		echo '<script type="application/json">';
		$google_analytics_array = [
			"vars" => ["account"=>$google_analytics_code],
			"triggers" => ["trackPageview" => ["on"=>"visible", "request"=>"pageview"] ] ];
		echo json_encode($google_analytics_array);
		echo '</script></amp-analytics>';
		endif;
	
	// if there is no need for a search header
	if (in_array($page_temp, ["create", "new", "add", "account", "website", "users"])): return; endif;
	
	echo "<amp-carousel height='150' layout='fixed-height' type='slides' id='navigation-carousel' data-parallax-factor='1.5'>";
	
	echo "<div id='navigation-carousel-main'>";

	global $login;
	if (empty($login)): echo "<div id='navigation-signin-button'><a href='/account/'><i class='material-icons'>account_box</i> Sign in</a></div>"; endif;
	if (!(empty($login))):
		echo "<div id='navigation-create-button' class='background_1'><a href='/create/'><i class='material-icons'>note_add</i> Create</a></div>";

		echo "<div id='navigation-settings-button'><a href='/account/'><i class='material-icons'>settings</i>";
		if ( (time() - $login['cookie_time']) >= 82800 ):
			echo "<span>Automatic logout soon. Save work and sign back in: <a href='/logout/'><b>log out</b></a>.</span>";
			endif;
		echo "</a></div>";

		endif;

	if (!(empty($page_temp))): echo "<div id='navigation-home-button'><a href='/'>Home</a></div>"; endif;

	echo "<div role='button' tabindex='0' on='tap:navigation-carousel.goToSlide(index=1)' id='navigation-search-button'>Search</div>";
	
	echo "</div>";
	
	echo "<div id='navigation-carousel-search' class='background_2'>";

	echo "<div role='button' tabindex='0' on='tap:navigation-carousel.goToSlide(index=0)' id='navigation-back-button'><i class='material-icons'>keyboard_arrow_left</i> Back</div>";

	echo "<div id='navigation-sitemap-button'><a href='/sitemap/'>Sitemap</a> &nbsp;&nbsp; | &nbsp;&nbsp; <a href='/schedule/'>Archive</a></div>";
	
	$search_value = null;
	if (array_intersect([$slug_temp, $page_temp], ["search"])): $search_value = htmlspecialchars($_SESSION['term'], ENT_QUOTES); endif;
	echo "<form method='get' action='/search/' target='_top'>";
	echo "<input id='navigation-search-input' type='search' name='term' placeholder='Search' value='".$search_value."' maxlength='45' autocomplete='off' required>";	
	echo "</form>";
		
	echo "</div>";
	
	echo "</amp-carousel>";
	
	global $page_confirmed;
	if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($page_confirmed[$page_temp]['page_id']))):
		echo "<div class='floating-action-button'>";
		echo "<a href='/".$page_temp."/edit/'>edit</a></div>";
		endif;
	
	global $media_confirmed;
	if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($media_confirmed[$slug_temp]['media_id']))):
		echo "<div class='floating-action-button'>";
		echo "<a href='/m/".$slug_temp."/edit/'>edit</a></div>";
		endif;

	}


function footer() {
	echo "<div class='footer_spacer'>&nbsp;</div>";
	echo "</body></html>"; exit; }


function login($disclaimer=null) {
	global $_POST;
	global $publisher;
	global $google_authenticator_toggle;

	$email = null; if (isset($_POST['checkpoint_email'])): $email = $_POST['checkpoint_email']; endif;

	html_header("Sign in to ".$publisher);
		
	echo "<div class='login'>";
	
	echo "<form id='login-form' method='post' action=''>";
	if (!(empty($disclaimer))): echo "<span>$disclaimer</span>"; endif;
	foreach ((array)$_POST as $name_temp => $value_temp):
		if (is_array($value_temp)):
			foreach ($value_temp as $name_temp_temp => $value_temp_temp): echo "<input type='hidden' name='".$name_temp_temp."[]' value='$value_temp_temp'>"; endforeach;
		else:
			echo "<input type='hidden' name='$name_temp' value='$value_temp'>"; endif; endforeach;

	echo "<div class='input-description'>Email address</div>";
	echo "<input type='email' name='checkpoint_email' placeholder='email' value='".$email."' autocomplete='off' required>";

	echo "<div class='input-description'>Password</div>";
	echo "<input type='password' name='checkpoint_password' placeholder='password' autocomplete='off' required>";
	if ($google_authenticator_toggle == "on"):
		echo "<div class='input-description' style='width: 285px;'>6-digit authenticator code</div>";
		echo "<input type='number' name='checkpoint_authenticator' placeholder='authenticator code' autocomplete='off' max='999999' style='width: 285px;' required>";
		endif;
	
	$disabled_temp = null;
	
	// if captcha key exists
	global $recaptcha_site; global $recaptcha_private; global $recaptcha_override;
	if (!(empty($recaptcha_site)) && !(empty($recaptcha_private)) && ($recaptcha_override !== "yes")):
		$disabled_temp = "disabled";
		echo "<style> .gray_background { background: none !important; color: #333 !important; } </style>";
		echo '<script> $(document).on("keypress", "input", function (e) { var code = e.keyCode || e.which; if (code == 13) { e.preventDefault(); return false; } }); </script>';
		echo '<script> function recaptchaval(){ document.getElementById("login-window-submit-button").disabled = false; document.getElementById("login-window-submit-button").classList.remove("gray_background"); document.getElementById("login-window-submit-button").classList.add("background_2"); } </script>';
		echo '<script> function recaptchainval(){ document.getElementById("login-window-submit-button").disabled = true; document.getElementById("login-window-submit-button").classList.add("gray_background"); document.getElementById("login-window-submit-button").classList.remove("background_2"); } </script>';
		echo "<div class='g-recaptcha' data-sitekey='".$recaptcha_site."' data-callback='recaptchaval' data-expired-callback='recaptchainval'></div>";
		endif;
	
	echo "<br><button id='login-window-submit-button' type='submit' name='login' value='continue' class='background_2 gray_background' $disabled_temp><i class='material-icons'>account_box</i> Sign in to ".$publisher."</button>";
	
	echo "</form></div>";
	echo "<a href='/'><div id='login-window-home-button'>Exit</div></a>";
	echo "</body></html>";
	footer(); }

function notfound () {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
	amp_header("404");
	echo "<h1>404: Not found.</h1>";
	footer(); } ?>
