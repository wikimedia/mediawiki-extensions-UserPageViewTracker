<?php

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'UserPageViewTracker',
	'author'=> array( 'Kimon Andreou', 'Luis Felipe Schenone' ),
	'descriptionmsg' => 'userpageviewtracker-desc',
	'version' => 0.3,
	'url' => 'https://www.mediawiki.org/wiki/Extension:UserPageViewTracker',
);

$wgExtensionMessagesFiles['UserPageViewTracker'] = __DIR__ . '/UserPageViewTracker.i18n.php';
$wgExtensionMessagesFiles['UserPageViewTrackerAlias'] = __DIR__ . '/UserPageViewTracker.alias.php';

$wgAutoloadClasses['SpecialUserPageViewTracker'] = __DIR__ . '/SpecialUserPageViewTracker.php';

$wgSpecialPages['UserPageViewTracker'] = 'SpecialUserPageViewTracker';

$wgHooks['ParserAfterTidy'][] = 'SpecialUserPageViewTracker::updateTable';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'SpecialUserPageViewTracker::updateDatabase';