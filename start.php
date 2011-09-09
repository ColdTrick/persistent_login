<?php 

	define("PERSISTENT_LOGIN_ANNOTATION", "persistent_login");

	function persistent_login_plugins_boot(){
		global $PERSISTENT_LOGIN;
		
		if(!isloggedin()){
			if(!empty($_COOKIE["elggperm"])){
				$PERSISTENT_LOGIN = true;
				$code = md5($_COOKIE["elggperm"]);
				
				if($users = get_entities_from_annotations("user", "", PERSISTENT_LOGIN_ANNOTATION, $code)){
					if(count($users) == 1){
						// login the one user
						$user = $users[0];
						// we have a user, log him in
						$_SESSION['user'] = $user;
						$_SESSION['id'] = $user->getGUID();
						$_SESSION['guid'] = $_SESSION['id'];
						$_SESSION['code'] = $_COOKIE['elggperm'];
					} else {
						// cleanup annotations
						if($annotations = get_annotations(0, "user", "", PERSISTENT_LOGIN_ANNOTATION, $code)){
							foreach($annotations as $annotation){
								$annotation->delete();
							}
						}
					}
				} else {
					// destroy old persistent cookie
					setcookie("elggperm", "", (time()-(86400 * 30)),"/");
					
					// forward to correctly set cookie and prevent deadloops
					forward();
				}
				
				$PERSISTENT_LOGIN = false;
			}
		}
	}
	
	function persistent_login_event_handler($event, $objecttype, $object){
		
		if(!empty($object) && ($object instanceof ElggUser)){
			if(!empty($_SESSION["code"])){
				$code = md5($_SESSION["code"]);
				
				create_annotation($object->getGUID(), PERSISTENT_LOGIN_ANNOTATION, $code, "text", $object->getGUID(), ACCESS_PRIVATE);
			}
		}
	}
	
	function persistent_login_logout_event_handler($event, $objecttype, $object){
		
		if(!empty($object) && ($object instanceof ElggUser)){
			
			if(!empty($_SESSION["code"])){
				$code = md5($_SESSION["code"]);
				
				if($annotations = get_annotations($object->getGUID(), "user", "", PERSISTENT_LOGIN_ANNOTATION, $code, $object->getGUID())){
					foreach($annotations as $annotation){
						$annotation->delete();
					}
				}
			}
		}
	}
	
	function persistent_login_permissions_hook($hook, $type, $returnvalue, $params){
		global $PERSISTENT_LOGIN;
		
		$result = $returnvalue;
		
		if(!empty($PERSISTENT_LOGIN)){
			if(!is_array($result)){
				$result = array($result);
			}
			
			$result[] = ACCESS_PRIVATE;
		}
		
		return $result;
	}
	
	function persistent_login_cron_hook($hook, $type, $returnvalue, $params){
		global $PERSISTENT_LOGIN;
		global $init_finished;
		
		$old_finished = $init_finished;
		$init_finished = false;
		$PERSISTENT_LOGIN = true;
		
		// delete persistent annotations older then permenent cookie lifetime
		$timeupper = time() - (86400 * 30);
		
		if($annotations = get_annotations(0, "user", "", PERSISTENT_LOGIN_ANNOTATION, "", 0, 9999, 0, "asc", 0, $timeupper)){
			foreach($annotations as $annotation){
				$annotation->delete();
			}
		}
		
		$init_finished = $old_finished;
		$PERSISTENT_LOGIN = false;
	}

	// register event listener
	register_elgg_event_handler("login", "user", "persistent_login_event_handler");
	register_elgg_event_handler("logout", "user", "persistent_login_logout_event_handler");

	register_elgg_event_handler("plugins_boot", "system", "persistent_login_plugins_boot", 1);
	
	// register plugin hooks
	register_plugin_hook("access:collections:read", "user", "persistent_login_permissions_hook");
	register_plugin_hook("cron", "daily", "persistent_login_cron_hook");
	
?>