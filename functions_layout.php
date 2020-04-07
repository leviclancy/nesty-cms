<? // html_header, amp_header, admin_bar, footer, login, notfound

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
	
	global $login;
	
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
	
//	// mostly for show-more features
//	echo '<script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script>';
	
	// for lightbox search feature
	echo '<script async custom-element="amp-lightbox" src="https://cdn.ampproject.org/v0/amp-lightbox-0.1.js"></script>';

	// for text fitting on images in particular
	echo '<script async custom-element="amp-fit-text" src="https://cdn.ampproject.org/v0/amp-fit-text-0.1.js"></script>';	
	
	// for the parallax
	echo '<script async custom-element="amp-fx-collection" src="https://cdn.ampproject.org/v0/amp-fx-collection-0.1.js"></script>';

//	// for the view more
//	echo '<script async custom-element="amp-accordion" src="https://cdn.ampproject.org/v0/amp-accordion-0.1.js"></script>';

	// for the adsense ads
//	if (!(empty($google_ad_client)) && !(empty($page_temp)) && !(in_array($page_temp, ["m", "search"]))):
//		echo '<script async custom-element="amp-auto-ads" src="https://cdn.ampproject.org/v0/amp-auto-ads-0.1.js"></script>';
//		endif;
	
	echo "<title>" . $title . "</title>";
	echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
	echo "<meta name='theme-color' content='".$color."'>";
	
//	echo "<base href='/' />";
	echo "<meta name='viewport' content='width=device-width,minimum-scale=1,initial-scale=1'>"; // must define viewport for amp
//	echo "<link rel='icon' sizes='192x192' href='icon.png'>";
//	echo "<link rel='apple-touch-icon' href='icon.png'>";

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

	echo "<div id='navigation-bar'>";
		echo "<div id='navigation-bar-home-button'><a href='/'>".$publisher."</a></div>";
		echo "<div id='navigation-bar-sitemap-button'><a href='/sitemap/'>Sitemap</a></div>";
		echo "<div id='navigation-bar-archive-button'><a href='/schedule/'>Archive</a></div>";
		echo "<div id='navigation-bar-search-button'>Search</div>";
		echo "</div>";

	// AMP LIGHTBOX FOR SEARCH
	echo "<form method='get' action='/search/' target='_top'>";
	echo "<input id='navigation-search-input' type='search' name='term' placeholder='Search' value='".$search_value."' maxlength='45' autocomplete='off' required>";	
	echo "</form>";
	
	if (empty($login)):
	
		endif;
	
	echo "<div id='account-bar'>";
		echo "<div id='account-bar-signin-button'><a href='/account/'><i class='material-icons'>account_box</i> Sign in</a></div>";
		echo "<div id='account-bar-signout-button'><a href='/account/'><i class='material-icons'>close</i> Sign out</a></div>";
		echo "<div id='account-bar-account-button'><a href='/account/'><i class='material-icons'>close</i> Sign out</a></div>";

		global $page_confirmed;
		if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($page_confirmed[$page_temp]['page_id']))):
			echo "<div id='account-bar-edit-button'><a href='/".$page_temp."/edit/'>edit</a></div>";
			endif;
	
		global $media_confirmed;
		if (!(empty($login)) && in_array($login['status'], ["contributor", "admin"]) && !(empty($media_confirmed[$slug_temp]['media_id']))):
			echo "<div id='account-bar-edit-button'><a href='/m/".$slug_temp."/edit/'>edit</a></div>";
			endif;
	
		echo "</div>";

	echo "<amp-lightbox class='login'>";
	
		echo "<form id='login-form' method='post' action=''>";
		foreach ((array)$_POST as $name_temp => $value_temp):
			if (is_array($value_temp)):
				foreach ($value_temp as $name_temp_temp => $value_temp_temp): echo "<input type='hidden' name='".$name_temp_temp."[]' value='$value_temp_temp'>"; endforeach;
			else:
				echo "<input type='hidden' name='$name_temp' value='$value_temp'>"; endif; endforeach;

		echo "<div class='input-description'>Email address</div>";
		echo "<input type='email' name='checkpoint_email' placeholder='email' value='".$email."' autocomplete='off' required>";

		echo "<div class='input-description'>Password</div>";
		echo "<input type='password' name='checkpoint_password' placeholder='password' autocomplete='off' required>";

		// if 2FA is enabled
		if ($google_authenticator_toggle == "on"):
			echo "<div class='input-description' style='width: 285px;'>6-digit authenticator code</div>";
			echo "<input type='number' name='checkpoint_authenticator' placeholder='authenticator code' autocomplete='off' max='999999' style='width: 285px;' required>";
			endif;
	
		// if captcha key exists
//		global $recaptcha_site; global $recaptcha_private; global $recaptcha_override;
//		if (!(empty($recaptcha_site)) && !(empty($recaptcha_private)) && ($recaptcha_override !== "yes")):
//			endif;
	
		echo "<br><button id='login-window-submit-button' type='submit' name='login' value='continue' class='background_2 gray_background' $disabled_temp><i class='material-icons'>account_box</i> Sign in to ".$publisher."</button>";
	
		echo "</form>";
		echo "<a href='/'><div id='login-window-home-button'>Exit</div></a>";
	
		echo "</amp-lightbox>";
	
	}


function footer() {
	echo "<div class='footer_spacer'>&nbsp;</div>";
	echo "</body></html>"; exit; }

function notfound () {
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
	amp_header("404");
	echo "<h1>404: Not found.</h1>";
	footer(); } ?>
