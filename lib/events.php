<?php

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
	
				$annotation_options = array(
					"guid" => $object->getGUID(),
					"annotation_name" => PERSISTENT_LOGIN_ANNOTATION,
					"annotation_value" => $code,
					"annotation_owner_guid" => $object->getGUID(),
					"limit" => false
				);
				
				elgg_delete_annotations($annotation_options);
			}
		}
	}