<?php

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UserPageViewTracker',
	'author'=> array( 'Kimon Andreou', 'Luis Felipe Schenone' ),
	'description' => 'Tracks the page views per page per user and displays it in [[Special:UserPageViewTracker]].',
	'descriptionmsg' => 'userpageviewtracker-desc',
	'version' => 0.3,
	'url' => 'http://www.mediawiki.org/wiki/Extension:UserPageViewTracker',
);

$wgExtensionMessagesFiles['UserPageViewTracker'] = __DIR__ . '/UserPageViewTracker.i18n.php';

$wgAutoloadClasses['SpecialUserPageViewTracker'] = __DIR__ . '/SpecialUserPageViewTracker.php';

$wgSpecialPages['UserPageViewTracker'] = 'SpecialUserPageViewTracker';

$wgHooks['ParserAfterTidy'][] = 'SpecialUserPageViewTracker::updateTable';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SpecialUserPageViewTracker::updateDatabase';