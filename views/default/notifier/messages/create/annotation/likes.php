<?php
/**
 * Display multiple likes as one notification message
 */

$notification = $vars['entity'];
$subjects = $notification->getSubjects();
$target_link = $vars['target_link'];
$subject_count = count($subjects);

$subject = $subjects[0];
$subject_link = elgg_view('output/url', array(
	'href' => $subject->getURL(),
	'text' => $subject->name,
	'is_trusted' => true,
));

if ($subject_count == 2) {
	$subject2 = $subjects[1];
	$subject2_link = elgg_view('output/url', array(
		'href' => $subject2->getURL(),
		'text' => $subject2->name,
		'is_trusted' => true,
	));

	$subtitle = elgg_echo('likes:notifications:summary:2', array($subject_link, $subject2_link, $target_link));
} else {
	// One of the users is mentioned by name so remove that user from the count
	$subject_count--;
	$subjects = elgg_echo('notifier:multiple_subjects', array($subject->name, $subject_count));

	// Full list of notification subject will be opened in a lightbox
	// TODO By default popup is on top of lightbox so should popup be used instead?

	$guid = $notification->getGUID();
	$subjects_link = elgg_view('output/url', array(
		'href' => "notifier/subjects/$guid",
		'text' => $subjects,
		'is_trusted' => true,
		'class' => 'elgg-lightbox',
	));

	$subtitle = elgg_echo('likes:notifications:summary:n', array($subjects_link, $target_link));
}

echo $subtitle;