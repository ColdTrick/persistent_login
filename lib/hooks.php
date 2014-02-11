<?php

/**
 * Access read hook to allow access to private data to functions of this plugin
 *
 * @param unknown_type $hook
 * @param unknown_type $type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 * @return Ambigous <string, unknown>
 */
function persistent_login_access_read_hook($hook, $type, $returnvalue, $params){
	global $PERSISTENT_LOGIN;

	$result = $returnvalue;

	if (!empty($PERSISTENT_LOGIN)) {
		if (!is_array($result)) {
			$result = array($result);
		}

		$result[] = ACCESS_PRIVATE;
	}

	return $result;
}

/**
 * Cron hook to delete persistent login annotations older then permanent cookie lifetime
 *
 * @param unknown_type $hook
 * @param unknown_type $type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 * @return void
 */
function persistent_login_cron_hook($hook, $type, $returnvalue, $params){
	global $PERSISTENT_LOGIN;

	$PERSISTENT_LOGIN = true;

	// delete persistent annotations older then permanent cookie lifetime
	$timeupper = time() - (86400 * 30);

	$annotation_options = array(
		"type" => "user",
		"annotation_name" => PERSISTENT_LOGIN_ANNOTATION,
		"limit" => false,
		"annotation_created_time_upper" => $timeupper
	);

	elgg_delete_annotations($annotation_options);

	$PERSISTENT_LOGIN = false;
}
