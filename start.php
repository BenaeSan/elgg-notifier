<?php
/**
 * Notifier
 * 
 * @package Notifier
 */

function notifier_init () {
	elgg_register_library('elgg:notifier', elgg_get_plugins_path() . 'notifier/lib/notifier.php');
	
	// Register the notifier's JavaScript
	$notifier_js = elgg_get_simplecache_url('js', 'notifier/notifier');
	elgg_register_simplecache_view('js/notifier/notifier');
	elgg_register_js('elgg.notifier', $notifier_js);
	elgg_load_js('elgg.notifier');
	
	/*
	$ia = elgg_set_ignore_access(true);
	$notifications = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'notification',
		'limit' => false,
		//'owner_guid' => elgg_get_logged_in_user_guid()
	));
	
	foreach ($notifications as $notification) {
		//$notification->status = 'unread';
		//$notification->save();
		$notification->delete();
	}
	elgg_set_ignore_access($ia);
	*/

	//elgg_dump($notifications);
	
	elgg_register_page_handler('notifier', 'notifier_page_handler');

	// add to the main css
	elgg_extend_view('css/elgg', 'notifier/css');

	register_notification_handler('notifier', 'notifier_notify_handler');
	
	elgg_register_plugin_hook_handler('notify:entity:message', 'object', 'notifier_message_body', 1);
	elgg_register_event_handler('create', 'annotation', 'notifier_comment_notifications');
	
	if (elgg_is_logged_in()) {
		// Add hidden popup module to topbar
		elgg_extend_view('page/elements/topbar', 'notifier/popup');

		// get unread notifications
		$num_notifications = (int)notifier_count_unread();
		
		$text = '<span class="elgg-icon elgg-icon-attention"></span>';
		$tooltip = elgg_echo("notifier:unreadcount", array($num_notifications));
		
		if ($num_notifications != 0) {
			$text .= "<span class=\"messages-new\">$num_notifications</span>";
		}

		// This link opens the popup module
		elgg_register_menu_item('topbar', array(
			'name' => 'notifier',
			'href' => '#notifier-popup',
			'text' => $text,
			'priority' => 600,
			'title' => $tooltip,
			'rel' => 'popup',
			'id' => 'notifier-popup-link',
		));
	}
}

function notifier_page_handler ($page) {
	elgg_load_library('elgg:notifier');
	
	switch ($page[0]) {
		case 'view':
			notifier_route_to_entity($page[1]);
			break;
		case 'all':
		default:
			$params = notifier_get_page_content_list();
	}
	
	$body = elgg_view_layout('content', $params);
	
	echo elgg_view_page('title', $body);
	
	return true;
}

/**
 * Notification handler
 *
 * @param ElggEntity $from
 * @param ElggUser   $to
 * @param string     $subject
 * @param string     $message
 * @param array      $params
 * @return bool
 */
function notifier_notify_handler(ElggEntity $from, ElggUser $to, $subject, $message, array $params = NULL) {

	return false;

	if (!$from) {
		throw new NotificationException(elgg_echo('NotificationException:MissingParameter', array('from')));
	}

	if (!$to) {
		throw new NotificationException(elgg_echo('NotificationException:MissingParameter', array('to')));
	}

	echo "<pre>";
	var_dump($from);
	echo "<hr />";
	var_dump($to);
	echo "<hr />";
	var_dump($subject);
	echo "<hr />";
	var_dump($message);
	echo "<hr />";
	var_dump($params);
	echo "</pre>";
	exit;

	$ia = elgg_set_ignore_access(true);
	$notification = new ElggNotification();
	$notification->title = $subject;
	$notification->description = $message;
	$notification->owner_guid = $to->getGUID();
	$notification->container_guid = $to->getGUID();
	$notification->access_id = ACCESS_PRIVATE;
	//$notification->save();
	elgg_set_ignore_access($ia);
	
	elgg_echo($notification);

	return true;
}

/**
 * Get the count of all unread notifications
 */
function notifier_count_unread () {
	return notifier_get_unread(array('count' => true));
}

function notifier_get_unread ($options = array()) {
	$defaults = array(
		'type' => 'object',
		'subtype' => 'notification',
		'limit' => false,
		'count' => true,
		'owner_guid' => elgg_get_logged_in_user_guid(),
		'metadata_name_value_pairs' => array(
			'name' => 'status',
			'value' => 'unread'
		),
		'order_by_metadata' => array(
			'name' => 'status',
			'direction' => DESC
		),
	);
	
	$options = array_merge($defaults, $options);
		
	return elgg_get_entities_from_metadata($options);
}

/**
 * Set the notification message body
 * 
 * @param string $hook    Hook name
 * @param string $type    Hook type
 * @param string $message The current message body
 * @param array  $params  Parameters about the blog posted
 * @return string
 */
function notifier_message_body($hook, $type, $message, $params) {
	$entity = $params['entity'];
	$to_entity = $params['to_entity'];
	$method = $params['method'];
	
	//$message = elgg_echo('notifier:message', array($subject));
	//$title = $CONFIG->register_objects[$entity->getType()][$entity->getSubtype()];
	
	$type = $entity->getType();
	$subtype = $entity->getSubtype();
	$title = "river:create:$type:$subtype";
	
	$ia = elgg_set_ignore_access(true);
	$notification = new ElggObject();
	$notification->subtype = 'notification';
	$notification->title = $title;
	$notification->description = $message;
	$notification->owner_guid = $to_entity->getGUID();
	$notification->container_guid = $to_entity->getGUID();
	$notification->access_id = ACCESS_PRIVATE;
	$notification->target_type = 'entity';
	$notification->target = $entity->getGUID();
    $notification->status = 'unread';
	$guid = $notification->save();
	elgg_set_ignore_access($ia);
	
	return $message;
}

/**
 * Manage comment notifications
 */ 
function notifier_comment_notifications($event, $type, $annotation) {
	if ($annotation->name == "generic_comment" || $annotation->name == "group_topic_post") {
		$title = 'riveraction:annotation:generic_comment';
		$message = 'river:comment:object:default';

		$entity = get_entity($annotation->entity_guid);
		$to_entity_guid = $entity->getOwnerGUID();

		$ia = elgg_set_ignore_access(true);
		$notification = new ElggObject();
		$notification->subtype = 'notification';
		$notification->title = $title;
		$notification->description = $message;
		$notification->owner_guid = $to_entity_guid;
		$notification->container_guid = $to_entity_guid;
		$notification->access_id = ACCESS_PRIVATE;
		$notification->target_type = 'annotation';
		$notification->target = $annotation->id;
		$notification->status = 'unread';
		$guid = $notification->save();
		elgg_set_ignore_access($ia);

		notifier_handle_mentions($annotation, 'annotation');
	}

	return TRUE;
}

function notifier_handle_mentions ($object, $type) {
	// This feature requires the mentions plugin
	if (!elgg_is_active_plugin('mentions')) {
		return false;
	}

	global $CONFIG;

	if ($type == 'annotation' && $object->name != 'generic_comment') {
		return NULL;
	}

	// excludes messages - otherwise an endless loop of notifications occur!
	if ($object->getSubtype() == "messages") {
		return NULL;
	}

	$fields = array(
		'name', 'title', 'description', 'value'
	);

	// store the guids of notified users so they only get one notification per creation event
	$notified_guids = array();

	foreach ($fields as $field) {

		$content = $object->$field;
		// it's ok in in this case if 0 matches == FALSE
		if (preg_match_all($CONFIG->mentions_match_regexp, $content, $matches)) {
			// match against the 2nd index since the first is everything
			foreach ($matches[1] as $username) {

				if (!$user = get_user_by_username($username)) {
					continue;
				}

				if ($type == 'annotation') {
					if ($parent = get_entity($object->entity_guid)) {
						$access = has_access_to_entity($parent, $user);
					} else {
						continue;
					}
				} else {
					$access = has_access_to_entity($object, $user);
				}

				// Override access
				// @todo What does the has_access_to_entity() do?
				$access = true;

				if ($user && $access && !in_array($user->getGUID(), $notified_guids)) {
					// if they haven't set the notification status default to sending.
					$notification_setting = elgg_get_plugin_user_setting('notify', $user->getGUID(), 'mentions');

					if (!$notification_setting && $notification_setting !== FALSE) {
						$notified_guids[] = $user->getGUID();
						continue;
					}

					// figure out the link
					switch($type) {
						case 'annotation':
							//@todo permalinks for comments?
							if ($parent = get_entity($object->entity_guid)) {
								$link = $parent->getURL();
							} else {
								$link = 'Unavailable';
							}
							break;
						default:
							$link = $object->getURL();
							break;
					}

					$owner = get_entity($object->owner_guid);
					$type_key = "mentions:notification_types:$type";
					if ($subtype = $object->getSubtype()) {
						$type_key .= ":$subtype";
					}

					$owner_link = elgg_view('output/url', array(
						'href' => $owner->getURL(),
						'text' => $owner->name,
					));

					$type_str = elgg_echo($type_key);

					$type_link = elgg_view('output/url', array(
						'href' => $link,
						'text' => $type_str,
					));

					$subject = 'mentions:notification:subject';
					//$subject = elgg_echo('mentions:notification:subject', array($owner_link, $type_link));
					$body = elgg_echo('mentions:notification:body', array($owner_link, $type_str, $content, $link));

					$ia = elgg_set_ignore_access(true);
					$notification = new ElggObject();
					$notification->subtype = 'notification';
					$notification->title = $subject;
					$notification->description = $body;
					$notification->owner_guid = $user->getGUID();
					$notification->container_guid = $user->getGUID();
					$notification->access_id = ACCESS_PRIVATE;
					$notification->target_type = 'other';
					$notification->target = $object->id;
					$notification->status = 'unread';
					$guid = $notification->save();
					elgg_set_ignore_access($ia);
				}
			}
		}
	}
}

elgg_register_event_handler('init', 'system', 'notifier_init');