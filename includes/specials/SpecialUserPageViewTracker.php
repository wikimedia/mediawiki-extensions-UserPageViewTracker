<?php

class SpecialUserPageViewTracker extends SpecialPage {

	function __construct() {
		parent::__construct( 'UserPageViewTracker' );
	}

	public static function updateTable( &$parser, &$text ) {
		global $wgOut;

		$wgOut->enableClientCache( false );
		$wgOut->addMeta( 'http:Pragma', 'no-cache' );

		$user = $parser->getUser();

		$dbw = wfGetDB( DB_MASTER );
		$user_id = $user->getID();
		$page_id = $parser->getTitle()->getArticleID();
		$hits = 0;
		$last = wfTimestampNow();

		$result = $dbw->select( 'user_page_views', [ 'hits','last' ], "user_id = $user_id AND page_id = $page_id", __METHOD__ );
		if ( $row = $result->fetchRow() ) {
			$hits = $row['hits'];
			$last = $row['last'];
		}
		$dbw->upsert(
			'user_page_views',
			[ 'user_id' => $user_id, 'page_id' => $page_id, 'hits' => $hits + 1, 'last' => $last ],
			[ [ 'user_id', 'page_id' ] ],
			[ 'user_id' => $user_id, 'page_id' => $page_id, 'hits' => $hits + 1, 'last' => $last ],
			__METHOD__
		);
		return true;
	}

	public static function updateDatabase( DatabaseUpdater $updater ) {
		global $wgDBprefix;
		$updater->addExtensionTable( $wgDBprefix . 'user_page_views', __DIR__ . '/../../sql/UserPageViewTracker.sql' );
		$updater->addExtensionTable( $wgDBprefix . 'user_page_hits', __DIR__ . '/../../sql/UserPageViewTracker.sql' );
		return true;
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
