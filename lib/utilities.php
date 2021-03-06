<?php
/**
 *	Elgg-markdown_wiki plugin
 *	@package elgg-markdown_wiki
 *	@author Emmanuel Salomon @ManUtopiK
 *	@license GNU Affero General Public License, version 3 or late
 *	@link https://github.com/ManUtopiK/elgg-markdown_wiki
 *
 *	Elgg-markdown_wiki utilities library
 **/

/**
 * Prepare the add/edit form variables
 *
 * @param ElggObject $page
 * @return array
 */
function markdown_wiki_prepare_form_vars($markdown_wiki = null, $container_guid = 0) {

	// input names => defaults
	$values = array(
		'title' => '',
		'description' => $_SESSION['convert_markdown_wiki'],
		'summary' => '',
		'access_id' => ACCESS_PUBLIC,
		'write_access_id' => ACCESS_LOGGED_IN,
		'tags' => '',
		'container_guid' => $container_guid ? $container_guid : elgg_get_page_owner_guid(),
		'guid' => null,
		'entity' => $markdown_wiki,
	);

	if ($markdown_wiki) {
		foreach (array_keys($values) as $field) {
			if (isset($markdown_wiki->$field)) {
				$values[$field] = $markdown_wiki->$field;
			}
		}
	}

	if (elgg_is_sticky_form('markdown_wiki')) {
		$sticky_values = elgg_get_sticky_values('markdown_wiki');
		foreach ($sticky_values as $key => $value) {
			$values[$key] = $value;
		}
	}

	elgg_clear_sticky_form('markdown_wiki');
	unset($_SESSION['convert_markdown_wiki']);

	return $values;
}


/**
 * Prepare the group settings form variables
 *
 * @param ElggObject $group A group object.
 * @return array
 */
function markdown_wiki_group_settings_prepare_form_vars($group = null) {

	$values = array(
		'markdown_wiki_all_enabled' => get_input('markdown_wiki_all_enabled', false),
	);

	if ($group) {
		foreach (array_keys($values) as $field) {
			if (isset($group->$field)) {
				$values[$field] = $group->$field;
			}
		}
	}

	if (elgg_is_sticky_form('markdown_wiki_settings')) {
		$sticky_values = elgg_get_sticky_values('markdown_wiki_settings');
		foreach ($sticky_values as $key => $value) {
			$values[$key] = $value;
		}
	}

	elgg_clear_sticky_form('markdown_wiki_settings');

	return $values;
}



/**
 * Get group by title
 *
 * @param string $group The title's group
 *
 * @return GUID|false Depending on success
 */
if (!function_exists('search_group_by_title')) {
	function search_group_by_title($group) {
		global $CONFIG, $GROUP_TITLE_TO_GUID_MAP_CACHE;

		$group = sanitise_string($group);

		// Caching
		if ((isset($GROUP_TITLE_TO_GUID_MAP_CACHE[$group]))
		&& (retrieve_cached_entity($GROUP_TITLE_TO_GUID_MAP_CACHE[$group]))) {
			return retrieve_cached_entity($GROUP_TITLE_TO_GUID_MAP_CACHE[$group]);
		}

		$guid = get_data("SELECT guid from {$CONFIG->dbprefix}groups_entity where name='$group'");

		if ($guid) {
			$GROUP_TITLE_TO_GUID_MAP_CACHE[$group] = $guid[0]->guid;
		} else {
			$guid = false;
		}

		if ($guid) {
			return $guid[0]->guid;
		} else {
			return false;
		}
	}
}



/**
 * Get markdown_wiki by title
 *
 * @param string $title The title's markdown_wiki
 * 		  integer $group Optionally, the GUID of a group
 *
 * @return ElggObject|false Depending on success
 */
function search_markdown_wiki_by_title($title, $group = null) {
	global $CONFIG, $MARKDOWN_WIKI_TITLE_TO_GUID_MAP_CACHE;

	$title = sanitise_string($title);

	// Caching
	if ((isset($MARKDOWN_WIKI_TITLE_TO_GUID_MAP_CACHE[$title]))) {
		return $MARKDOWN_WIKI_TITLE_TO_GUID_MAP_CACHE[$title];
	}

	$access = get_access_sql_suffix('e');
	$subtype_id = get_subtype_id('object', 'markdown_wiki');
	$group_sql = '';
	if ($group && is_numeric($group)) {
		$group_sql = "and e.container_guid='$group' ";
	}

	$query = "SELECT e.* from {$CONFIG->dbprefix}objects_entity u
		join {$CONFIG->dbprefix}entities e on e.guid=u.guid
		where e.subtype='$subtype_id' and convert(u.title using latin1) collate latin1_general_cs ='$title' and $access " . $group_sql;

	$entity = get_data($query);

	if ($entity) {
		$MARKDOWN_WIKI_TITLE_TO_GUID_MAP_CACHE[$title] = $entity[0]->guid;
	} else {
		$entity = false;
	}

	if ($entity) {
		return $entity[0]->guid;
	} else {
		return false;
	}
}


/**
 * Apply hooks to format markdown_wiki output
 *
 * @param	$text string with markdown syntax
 *			$guid GUID of the markdow_wiki object
 *
 * @return html
 */
function format_markdown_wiki_hooks($text, $guid = null) {

	$params = array('guid' => $guid);

	$result = elgg_trigger_plugin_hook('format_markdown', 'override', $params, null);
	if ($result) {
		return $result;
	}

	return elgg_trigger_plugin_hook('format_markdown', 'format', $params, $text);
}


/**
 * Calcul diff when edit a markdown_wiki text object
 *
 * @param string a markdown_wiki description
 *
 * @return array(chars added, chars deleted)
 */
function calc_diff_markdown_wiki($text) {
	$text = preg_replace('#<del>(.*)</del><ins>\1\r\n#sU','$1<ins>',$text);  //skip line break
	$text = preg_replace('#\r\n#sU','', $text);
	preg_match_all('#<ins>(.*)</ins>#sU', $text, $ins);
	preg_match_all('#<del>(.*)</del>#sU', $text, $del);

	return array(
		mb_strlen(implode($ins[1])),
		mb_strlen(implode($del[1])),
	);
}



/**
 * Return text of a markdown_wiki from entity
 * @param  Entity $markdown_wiki_entity   A markdown_wiki entity
 * @return string                     Text of the markdown_wiki page
 */
function get_markdown_wiki_text_from_entity($markdown_wiki_entity) {
	if (!$markdown_wiki_entity) {
		return false;
	} else {
		// parse markdown_wiki_entity
		$annotation = $markdown_wiki_entity->getAnnotations('markdown_wiki', 1, 0, 'desc');
		$value = unserialize($annotation[0]->value);
		return $value['text'];
	}
}



/**
 * Return text of a markdown_wiki from guid
 * @param  GUID $markdown_wiki_guid   The guid of the markdown_wiki page
 * @return string                     Text of the markdown_wiki page
 */
function get_markdown_wiki_text_from_guid($markdown_wiki_guid) {
	// get page
	$markdown_wiki_entity = get_entity($markdown_wiki_guid);
	if (!$markdown_wiki_entity) {
		return false;
	} else {
		return get_markdown_wiki_text_from_entity($markdown_wiki_entity);
	}
}







