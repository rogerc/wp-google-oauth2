<?php

//admin menu
add_action('admin_menu', 'google_auth_menu');
function google_auth_menu(){
        add_options_page(
            __('Google OAuth2', 'wp-google-oauth2'),
            __('Google OAuth2', 'wp-google-oauth2'),
            'manage_options',
            'google-oauth-settings',
            'google_auth_settings_page');
		add_action( 'admin_init', 'google_auth_register_settings' );
}

//register settings
function google_auth_register_settings() {
	//API
	register_setting( 'google_auth_settings_group', 'google_auth_app_name' );
	register_setting( 'google_auth_settings_group', 'google_auth_client_id' );
	register_setting( 'google_auth_settings_group', 'google_auth_client_secret' );
	register_setting( 'google_auth_settings_group', 'google_auth_redirect_url' );
	register_setting( 'google_auth_settings_group', 'google_auth_developer_key' );
    register_setting( 'google_auth_settings_group', 'google_auth_domain_filter' );
    register_setting( 'google_auth_settings_group', 'google_auth_wp_username_format');
}



//admin options page
function google_auth_settings_page(){

    $google_auth_app_name=esc_attr(get_option('google_auth_app_name'));
    if(!$google_auth_app_name){
        $google_auth_app_name=get_bloginfo('name');
    }
    $google_auth_client_id=esc_attr(get_option('google_auth_client_id'));
    $google_auth_client_secret=esc_attr(get_option('google_auth_client_secret'));
    $google_auth_redirect_url=esc_attr(get_option('google_auth_redirect_url'));
    if(!$google_auth_redirect_url){
        $google_auth_redirect_url=site_url();
    }
    $google_auth_developer_key=esc_attr(get_option('google_auth_developer_key'));
    $google_auth_domain_filter=esc_attr(get_option('google_auth_domain_filter'));
    $google_auth_wp_username_format=esc_attr(get_option('google_auth_wp_username_format'));


    ?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br /></div>
		<h2>Google OAuth Settings</h2>
        <br />
			<form method="post" action="options.php">
              <table class="form-table">
              <?php //API settings
				  settings_fields( 'google_auth_settings_group' );?>
                  <tr>
                  	<th colspan="2">
                    	<ul>
                          <li>Visit the <a rel="nofollow" target="_blank" href="https://code.google.com/apis/console/?api=plus">Google API Console</a> to generate your developer key, OAuth2 client id, OAuth2 client secret, and register your OAuth2 redirect uri. </li>
                          <li>Click on "Services" in the left column and turn on "Google+ API".</li>
                          <li>Click on "API Access" in the left column and click the button labeled "Create an OAuth2 client ID" </li>
                          <li>Give your application a name and click "Next" </li>
                          <li>Select "Web Application" as the "Application type" </li>
                          <li>Click on (more options and enter <?= site_url();?> into each textarea box)</li>
                          <li>Click "Create client ID" </li>
                          <li>Fill in the fields below with the generated information</li>
                        </ul>
                    </th>
                  </tr>
                  <tr valign="top">
                      <th>Product Name:</th>
                      <td><input type="text" name="google_auth_app_name" value="<?= $google_auth_app_name; ?>" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>Client ID:</th>
                      <td><input type="text" name="google_auth_client_id" value="<?= $google_auth_client_id; ?>" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>Client Secret:</th>
                      <td><input type="text" name="google_auth_client_secret" value="<?= $google_auth_client_secret; ?>" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>Redirect URI:</th>
                      <td><input type="text" name="google_auth_redirect_url" value="<?= $google_auth_redirect_url; ?>" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>API Key:</th>
                      <td><input type="text" name="google_auth_developer_key" value="<?= $google_auth_developer_key; ?>" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>Allowed domains:</th>
                      <td><input type="text" name="google_auth_domain_filter" value="<?= $google_auth_domain_filter; ?>" placeholder="comma separated list of domains" size="50" /></td>
                  </tr>
                  <tr valign="top">
                      <th>Username pattern:</th>
                      <td><input type="text" name="google_auth_wp_username_format" value="<?= $google_auth_wp_username_format; ?>" placeholder="first-last, first.last, first last" size="50" /></td>
                  </tr>
              </table>
              <p class="submit">
              <input type="submit" class="button-primary" value="Save Settings" />
              </p>
          </form>
    </div>

    <?php
    if($google_auth_app_name && $google_auth_client_id && $google_auth_client_secret && $google_auth_redirect_url && $google_auth_developer_key){
        if (!isset($_SESSION['token'])) {
            echo "Test your settings by clicking on the Google+ login button below:";
	        google_auth_login_button(true);
        }else{
            echo "You are logged in using Google account!";
        }
    }
}

?>
