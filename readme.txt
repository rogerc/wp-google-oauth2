=== WP Google OAuth2 ===
Contributors: Roger Castells
Tags: google oauth, google plus, plugin, login 
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.0.9
License: GPLv2 or later

Add Google OAuth2 login to site. If using bc_oauth2, existing users will be able to log in without creating new users.

== Description ==

Add Google OAuth2 login to site. It supports both regular google accounts and google apps. Google plus accounts should work too.

Only permissions it ask is email access and profile to populate name.

If using bc_oauth, existing users will be able to log in without creating new users.

== Installation ==

Upload the wp-google-oauth2 and activate. 

Follow instructions in the settings Google OAuth2 settings panel in WP.

You will need to create a new project on https://code.google.com/apis/console/?api=plus or get the credentials for an existing project.

== Screenshots ==

1.

== Changelog ==

= 0.0.9 =
* Removed for now checks on tokens. Once user is logged in, is up to WP to re-authenticate the user
* Added support for a google account to be linked to an existing WP account and as a native google account.
  If google ID found, it will also match the email address with the one linked to the WP user.

= 0.0.8 =
* If Google token is invalid/expired log user out

= 0.0.7 =
* Moved button to be below the loginform.

= 0.0.6 =
* Fixed bug with user lookup.
* Added error handling to capture exceptions when calling authenticate

= 0.0.5 =
* Refactored functions code to use wrapper.
* Fixed missing button bug when user logged out.

= 0.0.4 =
* Fixed google client wrapper.

= 0.0.3 =
* Created google client wrapper.

= 0.0.2 =
* Refactored functions code.

= 0.0.1 =
* Initial release