<?php
/**
 *	Elgg-markdown_wiki plugin
 *	@package elgg-markdown_wiki
 *	@author Emmanuel Salomon @ManUtopiK
 *	@license GNU Affero General Public License, version 3 or late
 *	@link https://github.com/ManUtopiK/elgg-markdown_wiki
 *
 *	Elgg-markdown_wiki history of a markdown_wiki page
 **/

elgg_load_library('markdown_wiki:fineDiff');

$markdown_wiki_guid = get_input('guid');

$user = elgg_get_logged_in_user_entity();
setlocale(LC_TIME, $user->language, strtolower($user->language) . '_' . strtoupper($user->language));

$markdown_wiki = get_entity($markdown_wiki_guid);
if (!$markdown_wiki) {

}

$container = $markdown_wiki->getContainerEntity();
if (!$container) {

}

if (elgg_instanceof($container, 'group')) {
	elgg_push_breadcrumb($container->name, "pages/group/$container->guid/all");
} else {
	elgg_push_breadcrumb($container->name, "pages/owner/$container->username");
}

elgg_push_breadcrumb($markdown_wiki->title, $markdown_wiki->getURL());
elgg_push_breadcrumb(elgg_echo('markdown_wiki:history'));

$title = $markdown_wiki->title . ": " . elgg_echo('markdown_wiki:history');

$annotations = array_reverse(elgg_get_annotations(array(
	'types' => 'object',
	'subtypes' => 'markdown_wiki',
	'annotation_names' => 'markdown_wiki',
	'guids' => $markdown_wiki_guid,
	'order_by' => 'time_created desc',
	'limit' => 50,
	)));

foreach($annotations as $key => $annotation) {
	$values[] = unserialize($annotation->value);
}
global $fb; $fb->info($value);
$diffHTML = $diffOwner = '';
for($i=count($annotations)-1; $i>=0; $i--) {
	if ($i != 0) {
		$diff[$i] = new FineDiff($values[$i-1]['text'], $values[$i]['text'], array(
			FineDiff::paragraphDelimiters,
			FineDiff::sentenceDelimiters,
			FineDiff::wordDelimiters,
			FineDiff::characterDelimiters
			));
		$diffHTML .= "<div id='diff-$i' class='diff hidden'>" . $diff[$i]->renderDiffToHTML() . '</div>';
	} else {
		$diffHTML .= "<div id='diff-0' class='diff hidden'>" . $values[0]['text'] . '</div>';
	}
	$owner = get_entity($annotations[$i]->owner_guid);
	$owner_link = elgg_echo('markdown_wiki:history:date', array("<a href=\"{$owner->getURL()}\">$owner->name</a>"));
	$time = ucwords(htmlspecialchars(strftime(elgg_echo('markdown_wiki:history:date_format'), $annotations[$i]->time_created)));
	$summary = $values[$i]['summary'];
	$array_diff = $values[$i]['diff'];
	$diff_text = '<ins>&nbsp;+' . $array_diff[0] . '&nbsp;</ins><del>&nbsp;-' . $array_diff[1] . '&nbsp;</del>';
	
$diffOwner .= <<<HTML
<div id='owner-$i' class='owner prm'>
	$summary<br/>
	<span class="elgg-subtext">
		$diff_text $owner_link $time
	</span>
</div>
HTML;
}

$diff_annotation = $annotations[count($annotations)-1];
$diff_annotation->value = $diffHTML;
$content = elgg_view_annotation($diff_annotation);
$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('markdown_wiki/history_sidebar', array('diffOwner' => $diffOwner)),
	'class' => 'fixed-sidebar',
));

echo elgg_view_page($title, $body);
