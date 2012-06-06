<?php
/**
 *	Elgg-markdown_wiki plugin
 *	@package elgg-markdown_wiki
 *	@author Emmanuel Salomon @ManUtopiK
 *	@license GNU Affero General Public License, version 3 or late
 *	@link https://github.com/ManUtopiK/elgg-markdown_wiki
 *
 *	Elgg-markdown_wiki owner page
 **/

$owner = elgg_get_page_owner_entity();
if (!$owner) {
	forward('markdown_wiki/all');
}

// access check for closed groups
group_gatekeeper();

$title = elgg_echo('markdown_wiki:owner', array($owner->name));

elgg_push_breadcrumb($owner->name);

elgg_register_menu_item('title', array(
	'name' => 'new',
	'href' => "#",
	'text' => elgg_echo('markdown_wiki:new'),
	'link_class' => 'elgg-button elgg-button-action',
));

if (elgg_instanceof($owner, 'group')) {
	$content = elgg_list_entities(array(
		'types' => 'object',
		'subtypes' => 'markdown_wiki',
		'container_guid' => $owner->guid,
		'full_view' => false,
	));
} else {
	$content = elgg_list_entities(array(
		'types' => 'object',
		'subtypes' => 'markdown_wiki',
		'owner_guid' => $owner->guid,
		'full_view' => false,
	));
}

if (!$content) {
	$content = '<p>' . elgg_echo('markdown_wiki:none') . '</p>';
}

$filter_context = '';
if ($owner->guid == elgg_get_logged_in_user_guid()) {
	$filter_context = 'mine';
}

$sidebar = elgg_view('markdown_wiki/sidebar');

$params = array(
	'filter_context' => $filter_context,
	'content' => $content,
	'title' => $title,
	'sidebar' => $sidebar,
);

if (elgg_instanceof($owner, 'group')) {
	$params['filter'] = '';
}

$body = elgg_view_layout('content', $params);

echo elgg_view_page($title, $body);
