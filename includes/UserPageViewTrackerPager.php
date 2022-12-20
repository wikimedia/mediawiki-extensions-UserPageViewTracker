<?php

use MediaWiki\MediaWikiServices;

class UserPageViewTrackerPager extends AlphabeticPager {

	/** @var int */
	protected $rowCount = 0;

	function __construct( IContextSource $context, $username = null ) {
		parent::__construct( $context );
		global $wgRequest;
		$this->filterUsers = $wgRequest->getVal( 'filterusers' );
		$this->filterUserList = $this->filterUsers !== null ? explode( "|", $this->filterUsers ) : [];
		$this->ignoreUsers = $wgRequest->getVal( 'ignoreusers' );
		$this->ignoreUserList = $this->ignoreUsers !== null ? explode( "|", $this->ignoreUsers ) : [];
	}

	/**
	 * Implementing remaining abstract method
	 *
	 * @return string
	 */
	function getIndexField() {
		return "rownum";
	}

	function getExtraSortFields() {
		return [ 'u.user_id', 'hits DESC' ];
	}

	function getQueryInfo() {
		$conds = [];
		if ( $this->filterUsers ) {
			$includeUsers = "user_name in ( '";
			$includeUsers .= implode( "', '", $this->filterUserList ) . "')";
			$conds[] = $includeUsers;
		}
		if ( $this->ignoreUsers ) {
			$excludeUsers = "user_name not in ( '";
			$excludeUsers .= implode( "', '", $this->ignoreUserList ) . "')";
			$conds[] = $excludeUsers;
		}
		$conds[] = 'u.user_id=v.user_id AND p.page_id=v.page_id';
		$prefix = $this->getConfig()->get( 'DBprefix' );
		return [
			'tables' => '(' . $prefix . 'user u JOIN ' . $prefix . 'page p) JOIN ' . $prefix . 'user_page_views v',
			'fields' => [
				'rownum' => '@rownum+1',
				'user_name' => 'u.user_name',
				'user_real_name' => 'u.user_real_name',
				'page_namespace' => 'p.page_namespace',
				'page_title' => 'p.page_title',
				'hits' => 'v.hits',
				'last' => 'v.last',
				"concat(substr(last, 1, 4),'-',substr(last,5,2),'-',substr(last,7,2),' ',substr(last,9,2),':',substr(last,11,2),':',substr(last,13,2)) AS last"
			], 'conds' => $conds
		];
	}

	function formatRow( $row ) {
		$userPage = Title::makeTitle( NS_USER, $row->user_name );
		$name = Linker::link( $userPage, htmlspecialchars( $userPage->getText() ) );
		$pageTitle = Title::makeTitle( $row->page_namespace, $row->page_title );
		if ( $row->page_namespace > 0 ) {
			$pageFullName = $pageTitle->getNsText() . ':' . htmlspecialchars( $pageTitle->getText() );
		} else {
			$pageFullName = htmlspecialchars( $pageTitle->getText() );
		}
		$page = Linker::link( $pageTitle, $pageFullName );

		$res = '<tr>';
		$res .= '<td>' . $name . '</td><td>';
		$res .= "$page</td>";
		$res .= '<td style="text-align:right">' . $row->hits . '</td>';
		$res .= '<td style="text-align:center">' . $row->last . '</td>';
		$res .= "</tr>\n";
		return $res;
	}

	function getBody() {
		if ( !$this->mQueryDone ) {
			$this->doQuery();
		}
		if ( method_exists( MediaWikiServices::class, 'getLinkBatchFactory' ) ) {
			// MW 1.35+
			$batch = MediaWikiServices::getInstance()->getLinkBatchFactory()->newLinkBatch();
		} else {
			$batch = new LinkBatch;
		}
		$db = $this->mDb;
		$this->mResult->rewind();
		$this->rowCount = 0;
		while ( $row = $this->mResult->fetchObject() ) {
			$batch->addObj( Title::makeTitleSafe( NS_USER, $row->user_name ) );
		}
		$batch->execute();
		$this->mResult->rewind();
		return parent::getBody();
	}

	function getForm() {
		$formDescriptor = [
			'filterusers' => [
				'type' => 'textwithbutton',
				'name' => 'filterusers',
				'label' => 'Usernames:',
				'default' => $this->filterUsers,
				'buttondefault' => 'Filter',
			],
			'ignoreusers' => [
				'type' => 'textwithbutton',
				'name' => 'ignoreusers',
				'label' => 'Usernames:',
				'default' => $this->ignoreUsers,
				'buttondefault' => 'Exclude',
			]
		];

		$context = new DerivativeContext( $this->getContext() );
		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $context );
		$htmlForm
			->setId( 'filteruser' )
			->setName( 'filteruser' )
			->suppressDefaultSubmit()
			->setWrapperLegend( null )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Preserve filter offset parameters when paging
	 * @return array
	 */
	function getDefaultQuery() {
		$query = parent::getDefaultQuery();
		if ( $this->filterUsers != '' ) {
			$query['filterusers'] = $this->filterUsers;
		}
		if ( $this->ignoreUsers != '' ) {
			$query['ignoreusers'] = $this->ignoreUsers;
		}
		return $query;
	}
}
