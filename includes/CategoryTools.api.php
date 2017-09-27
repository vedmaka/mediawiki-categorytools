<?php

class CategoryToolsAPI extends ApiBase {

	private $parsedParams = array();
	private $formattedData = array(
		'error' => 'something gone wrong, please contact site administrator'
	);

	public function execute() {
		$this->parsedParams = $this->extractRequestParams();
		$method = $this->parsedParams['method'];
		switch ($method) {
			case 'rename':
				// TODO: ..
				break;
			case 'delete':
				$this->delete();
				break;
			case 'read':
				$this->read();
				break;
		}
		//$this->getResult()->addValue(null,null, $this->formattedData);
		die( json_encode($this->formattedData) );
	}

	private function delete() {

		global $wgContLang;

		$categoryId = $this->parsedParams['id'];
		$category = Category::newFromTitle( Title::newFromID($categoryId) );

		if( !$category ) {
			return false;
		}

		$categoryNamespace = $wgContLang->getNsText( NS_CATEGORY );
		$pattern = "\[\[({$categoryNamespace}):([^\|\]]*)(\|[^\|\]]*)?\]\]";
		$cleanText = '';

		$pages = $category->getMembers();
		/** @var Title $p */
		foreach ($pages as $p) {
			if( $p->getNamespace() !== NS_MAIN ) {
				// Cleanup the category markup
				$wp = WikiPage::newFromID($p->getArticleID());
				$pageText = $wp->getContent()->getWikitextForTransclusion();
				// Check linewise for category links:
				foreach ( explode( "\n", $pageText ) as $textLine ) {
					// Filter line through pattern and store the result:
					$cleanText .= preg_replace( "/{$pattern}/i", "", $textLine ) . "\n";
				}
				// Place the cleaned text into the text box:
				$cleanText = trim( $cleanText );
				$wp->doEditContent(new WikitextContent($cleanText), 'Removed category by CategoryTools');
				$wp->doPurge();
			}
		}

		$this->formattedData = array('status' => 'success');

	}

	private function read() {
		$categoriesToRender = array();
		$categories = CategoryTools::getAllCategories();
		foreach ($categories as $category) {
			$catItem['text'] = $category->getTitle()->getText();
			$catItem['id'] = ''.$category->getTitle()->getArticleID();
			$catItem['children'] = array();
			$catItem['data']['url'] = $category->getTitle()->getFullURL();
			$catItem['data']['members_count'] = 0;
			$members = $category->getMembers();
			/** @var Title $member */
			foreach ($members as $member) {
				if( $member->getNamespace() != NS_CATEGORY ) {
					$catItem['data']['members_count']++;
					continue;
				}
				$catItem['children'][] = array(
					'text' => $member->getText(),
					'id' => $member->getArticleID(),
					'children' => array(),
					'data' => array( 'url' => $member->getFullURL() )
				);
			}
			$categoriesToRender[] = $catItem;
		}
		$this->formattedData = $categoriesToRender;
	}

	public function getAllowedParams( /* $flags = 0 */ ) {
		return array_merge(parent::getAllowedParams(),
		array(
			'method' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'new_category_name' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
		));
	}

}