<?php

class UserPageViewTrackerPager extends AlphabeticPager {
	protected $rowCount = 0;

	function __construct( IContextSource $context, $username = null ) {
		parent::__construct( $context );
		global $wgRequest;
		$this->filterUsers = $wgRequest->getVal( 'filterusers' );
		$this->filterUserList = explode( "|", $this->filterUsers );
		$this->ignoreUsers = $wgRequest->getVal( 'ignoreusers' );
		$this->ignoreUserList = explode( "|", $this->ignoreUsers );
	}

	// Implementing remaining abstract method
	function getIndexField() {
		return "rownum";
	}

	function getQueryInfo() {
		global $wgDBprefix;
		list( $userpagehits ) = wfGetDB( DB_REPLICA )->tableNamesN( 'user_page_hits' );
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
		$table = "(select @rownum:=@rownum+1 as rownum,";
		$table .= "user_name, page_namespace, page_title,hits, last ";
		$table .= "from (select @rownum:=0) r, ";
		$table .= "(select user_name, page_namespace, page_title,hits,";
		$table .= "last from " . $wgDBprefix . "user_page_hits) p) results";
		return [
			'tables' => " $table ",
			'fields' => [ 'rownum',
			'user_name',
			'page_namespace',
			'page_title',
			'hits',
			"concat(substr(last, 1, 4),'-',substr(last,5,2),'-',substr(last,7,2),' ',substr(last,9,2),':',substr(last,11,2),':',substr(last,13,2)) AS last" ],
			'conds' => $conds
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
		$batch = new LinkBatch;
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
