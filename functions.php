<?php

//******************************************************************************************************************
//Google+ Login 

$debug_oauth = true;

require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_Oauth2Service.php';
require_once 'GoogleOAuth.class.php';

function get_config($with_defaults = false) {
	$config = array();
	$config['app_name'] = esc_attr(get_option('google_auth_app_name'));
	$config['client_id'] = esc_attr(get_option('google_auth_client_id'));
	$config['client_secret'] = esc_attr(get_option('google_auth_client_secret'));
	$config['redirect_url'] = esc_attr(get_option('google_auth_redirect_url'));
	$config['developer_key'] = esc_attr(get_option('google_auth_developer_key'));
	$config['domain_filter'] = explode(",", preg_replace("/\s+/", "", esc_attr(get_option('google_auth_domain_filter'))));;
	$config['wp_username_format'] = esc_attr(get_option('google_auth_wp_username_format'));
	if ($config['domain_filter'][0] == "") {
		$config['domain_filter'] = null;
	}
	if (!trim($config['wp_username_format'] && $with_defaults)) {
		$config['wp_username_format'] = "first-last";
	}
	$config['approval_prompt'] = 'auto';
	$config['access_type'] = 'offline';
	return $config;
}

$config = get_config(true);
$oAuthClient = new GoogleOAuth($config);

//Google OAuth2
add_action('init', 'google_auth_init');
function google_auth_init($button = false) {
	global $error, $oAuthClient, $config;
	session_start();
//	$config = get_config(true);
//	$oAuthClient = new GoogleOAuth($config);

	// validate Google's response/redirect
	if (isset($_GET['code'])) {
		try {
			$token = $oAuthClient->authenticate($_GET['code']);
			logMsg("Access token: " . print_r($token, true));
			$_SESSION['token'] = $token;
		} catch (Google_AuthException $e) {
			error_log("Exception logging in user: " . $e->getMessage());
			loginFailed("System encountered error. Please try again.");
		}

		$g_user = $oAuthClient->getUserInfo();
		$g_email = $oAuthClient->getEmailAddress();
		$g_domain = $oAuthClient->getUserDomain();
		$g_id = $oAuthClient->getID();

		logMsg($g_user);

		// check if domain is allowed. if not alloed do nothing.
		if (is_null($config['domain_filter']) || in_array($g_domain, $config['domain_filter'], true)) {

			// did we get an id back from google?
			if ($g_id) {

				// get current WP user if logged in.
				$current_user = wp_get_current_user();
				$user_id = $current_user->ID;

				// user already logged into WP
				if ($user_id != 0) {

					// linking google account to existing WP account
					if (!get_user_meta($user_id, 'google_auth_userid')) {
						update_user_meta($user_id, 'google_auth_userid', $g_id);
					}

				// user is not logged in
				} else {

					// checking to see if g_id exists in the DB
					$users1 = get_users(array('meta_key' => 'google_auth_userid', 'meta_value' => $g_id));
					if(is_array($users1)){
						while(list($idx,$user) = each($users1)){
							if($user->data->user_email == $g_email){
								$user_id = (int)($user->ID);
							}
						}
					}


					logMsg($users1);
					// if we can't find google_auth_userid, will try to look for bc-oauth key and match email address
					if ($user_id < 1) {
						$users2 = get_users(array('meta_key' => 'bc_oauth_google_id', 'meta_value' => $g_email));
						$user_id = (int)($users2[0]->ID);
					}

					// if user doesn't exist, create a new one.
					if (!$user_id) {
						logMsg("User does not exist. Creating new user: $g_id / $g_email");

						//create username using pattern set in wp_username_format settings
						$user_name = preg_replace(
							array('/first/', '/last/'),
							array($g_user['given_name'], $g_user['family_name']),
							$config['wp_username_format']);
						$user_name = sanitize_user($user_name);

						//check if username exists. if exists add random numbers at the end.
						$i = 0;
						while(username_exists($user_name)) {
							if($i > 2){
								loginFailed("Tried 3 times to create user. Please try again.");
								break;
							}
							$user_name = $user_name . rand(1000, 9999);
							$i++;
						}
						logMsg("Creating new user for username: $user_name");
						$random_password = wp_generate_password(12, false);
						// create user in WP database
						$user_id = wp_create_user($user_name, $random_password, $g_email);

						// if email address already used, get user from email address
						if (!is_int($user_id)) {
							$user = get_user_by("email", $g_email);
							$user_id = $user->ID;
						} else {
							// update core details: display_name
							wp_update_user(array("ID" => $user_id, 'display_name' => $g_user['given_name'] . " " . $g_user['family_name']));
							// add user meta data
							update_user_meta($user_id, 'nickname', $g_user['name']);
							update_user_meta($user_id, 'display_name', $g_user['name']);
							update_user_meta($user_id, 'first_name', $g_user['given_name']);
							update_user_meta($user_id, 'last_name', $g_user['family_name']);
						}
						// update user metadata with google auth id
						update_user_meta($user_id, 'google_auth_userid', $g_id);

						// if user exists but has no google_auth_userid, set it
					} else if ((int)$users2[0]->ID > 0) {
						update_user_meta($user_id, 'google_auth_userid', $g_id);
					}


				}
				// update token and redirect to homepage
				update_user_meta($user_id, 'google_auth_token', $_SESSION['token']);
				$refreshToken = $oAuthClient->getRefreshToken();
				if (!empty($refreshToken)) {
					update_user_meta($user_id, 'google_auth_refresh_token', $refreshToken);
				}

				// login user and redirect to site_url
				wp_set_auth_cookie($user_id, false, is_ssl());
				wp_redirect(site_url());
				exit();
			} else {
				loginFailed("There was a problem with login (ID missing).");
			}

		} else {
			loginFailed("Access restricted to certain domains.");

		}
		return;
	}

	// clear token and session if logged out.
	if (isset($_REQUEST['logout']) || isset($_REQUEST['loggedout'])) {
		unset($_SESSION['token']);
		session_destroy();
	}

	// use token in session and validate.
	// FEATURE OFF FOR NOW as would require existing users to approve permissions again to allow offline access.
	if (isset($_SESSION['token']) && false) {
		$oAuthClient->setAccessToken($_SESSION['token']);
		if ($oAuthClient->validateToken() && false) {
		} else {
			$refreshToken = get_user_meta(wp_get_current_user()->ID, "google_auth_refresh_token");
			if ($oAuthClient->refreshAccessToken($refreshToken[0])) {
				$_SESSION['token'] = $oAuthClient->getAccessToken();
			} else {
				sessionHasExpired();
			}
		}

	}


	// check token is valid and update session in case token has changed


}

//kill oauth session on wp log out
add_action('wp_logout', 'google_auth__logout');
function google_auth_logout() {
	unset($_SESSION['token']);
	wp_clear_auth_cookie();
	session_destroy();
}

//google login button on wp login screen
add_action('login_footer', 'google_auth_login_button');
function google_auth_login_button($admin = false) {
	global $_SESSION, $oAuthClient,$config;
	// display login button
	if (!$_SESSION['state']) {
		$_SESSION['state'] = mt_rand();
	}
	$authUrl = $oAuthClient->getAuthURL("https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile", $_SESSION['state']);
	if (GoogleOAuth::validateConfig($config)) {

		if (isset($_SESSION["login_message"])) {
			unset($_SESSION["login_message"]);
		}
		if(!$admin){
		?>
		<script type="text/javascript">
			var el = document.getElementById("loginform");
			var html = "<a href='<?= $authUrl ?>' class='gappslink'><div class='gappsbutton'><span class='gappsicon'></span>Log in using Google</div></a>";

			// Internet Explorer, Opera, Chrome, Firefox 8+ and Safari
			if (el.insertAdjacentHTML) {
				el.insertAdjacentHTML("afterend", html);
			} else {
				document.write("<a href='<?= $authUrl ?>' class='gappslink'><div class='gappsbutton'><span class='gappsicon'></span>Log in using Google</div></a>");
			}
		</script>
		<link rel='stylesheet' href='<?= plugins_url('style.css', __FILE__) ?>' type='text/css' media='all'>
		<?php
		}else{
			echo "<link rel='stylesheet' href='".plugins_url('style.css', __FILE__)."' type='text/css' media='all'>";
			echo "<a href='$authUrl' class='gappslink'><div class='gappsbutton'><span class='gappsicon'></span>Log in using Google</div></a>";
		}

	}
	return;
}

// add login error message generated from google auth.
add_action('login_head', 'add_login_error', 1);
function add_login_error() {
	global $error;
	if (isset($_SESSION["login_message"])) {
		$error = "<strong>ERROR</strong>: " . $_SESSION["login_message"];
	}
}

// debug log
function logMsg($msg) {
	global $debug_oauth;
	if ($debug_oauth) {
		error_log(print_r($msg, true) . "\n", 3, "/tmp/google_oauth.debug.log");
	}
}

// common steps when login fails
function loginFailed($msg) {
	global $_SESSION;
	session_destroy();
	session_start();
	$_SESSION["login_message"] = $msg;
	wp_redirect(wp_login_url());
	exit;
}

// when session has expired
function sessionHasExpired() {
	global $_SESSION;
	google_auth_logout();
	session_start();
	$_SESSION["login_message"] = "Your session has expired.";
}


?>