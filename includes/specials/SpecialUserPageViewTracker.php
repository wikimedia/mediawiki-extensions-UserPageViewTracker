<?php

class SpecialUserPageViewTracker extends SpecialPage {

	function __construct() {
		parent::__construct( 'UserPageViewTracker' );
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$dbw = wfGetDB( DB_MASTER );
		if ( method_exists( $skin, 'getUserIdentity' ) ) {
			// MW 1.36+
			$user = $skin->getUserIdentity();
		} else {
			$user = $skin->getUser();
		}
		$user_id = $user->getID();
		$page_id = $skin->getTitle()->getArticleID();
		if ( !$user_id || !$page_id ) {
			return;
		}
		$last = $dbw->timestamp( TS_UNIX );
		$hits = $dbw->selectField( 'user_page_views', 'hits', "user_id = $user_id AND page_id = $page_id" );
		if ( $hits ) {
			$hits++;
		}
		$dbw->upsert( 'user_page_views',
			[ 'user_id' => $user_id, 'page_id' => $page_id, 'hits' => 1, 'last' => $last ],
			[ [ 'user_id', 'page_id' ] ],
			[ 'hits' => $hits, 'last' => $last ]
		);
	}

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'user_page_views', __DIR__ . '/../../sql/UserPageViewTracker.sql' );
	}

	function execute( $parser = null ) {
		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setPageTitle( 'User page view tracker' );

		if ( method_exists( $request, 'getLimitOffsetForUser' ) ) {
			// MW 1.35+
			list( $limit, $offset ) = $request->getLimitOffsetForUser( $user );
		} else {
			list( $limit, $offset ) = $request->getLimitOffset();
		}

		$userTarget = isset( $parser ) ? $parser : $request->getVal( 'username' );

		$pager = new UserPageViewTrackerPager( $this->getContext(), $user );
		$form = $pager->getForm();
		$body = $pager->getBody();
		$html = $form;
		if ( $body ) {
			$html .= $pager->getNavigationBar();
			$html .= '<table class="wikitable" width="100%" cellspacing="0" cellpadding="0">';
			$html .= '<tr><th>Username</th><th>Page</th><th>Views</th><th>Last</th></tr>';
			$html .= $body;
			$html .= '</table>';
			$html .= $pager->getNavigationBar();
		} else {
			$html .= '<p>' . $this->msg( 'listusers-noresult' )->escaped() . '</p>';
		}
		$out->addHTML( $html );
	}
}
