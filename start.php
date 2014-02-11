<?php

define("PERSISTENT_LOGIN_ANNOTATION", "persistent_login");

require_once(dirname(__FILE__) . "/lib/events.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");

/**
 * Boot function to login a user based on the elggperm cookie
 */
function persistent_login_plugins_boot(){
	global $PERSISTENT_LOGIN;

	// register event listener
	elgg_register_event_handler("login", "user", "persistent_login_event_handler");
	elgg_register_event_handler("logout", "user", "persistent_login_logout_event_handler");

	// register plugin hooks
	elgg_register_plugin_hook_handler("access:collections:read", "user", "persistent_login_access_read_hook");
	elgg_register_plugin_hook_handler("cron", "daily", "persistent_login_cron_hook");

	// now check if we need to do anything
	if (!elgg_is_logged_in()) {

		if (!empty($_COOKIE["elggperm"])) {

			$PERSISTENT_LOGIN = true;
			$code = md5($_COOKIE["elggperm"]);

			$annotation_options = array(
				"type" => "user",
				"annotation_name_value_pairs" => array(
					"name" => PERSISTENT_LOGIN_ANNOTATION,
					"value" => $code
				)
			);

			if ($users = elgg_get_entities_from_annotations($annotation_options)) {
				if (count($users) == 1) {
					// login the one user
					$user = $users[0];

					// we have a user, log him in
					$_SESSION['user'] = $user;
					$_SESSION['id'] = $user->getGUID();
					$_SESSION['guid'] = $_SESSION['id'];
					$_SESSION['code'] = $_COOKIE['elggperm'];
				} else {
					$annotation_cleanup_options = array(
						"type" => "user",
						"annotation_name" => PERSISTENT_LOGIN_ANNOTATION,
						"annotation_value" => $code,
						"limit" => false
					);

					// cleanup annotations
					elgg_delete_annotations($annotation_cleanup_options);
				}
			} else {

				// destroy old persistent cookie
				setcookie("elggperm", "", (time() - (86400 * 30)), "/");

				// forward to correctly set cookie and prevent deadloops
				forward();
			}

			$PERSISTENT_LOGIN = false;
		}
	}
}

elgg_register_event_handler("plugins_boot", "system", "persistent_login_plugins_boot", 1);
