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
				$this->rename();
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

	private function rename() {
		global $wgContLang;

		$categoryId = $this->parsedParams['id'];
		$category = Category::newFromTitle( Title::newFromID($categoryId) );
		$categoryName = $category->getTitle()->getText();
		$newName = trim($this->parsedParams['new_category_name']);

		if( !$category ) {
			return false;
		}
		if( !$newName ) {
			return false;
		}
		$testCategory = Title::newFromText($newName, NS_CATEGORY);
		if( $testCategory->exists() ) {
			return false;
		}

		$categoryNamespace = $wgContLang->getNsText( NS_CATEGORY );
		$pattern = "\[\[({$categoryNamespace}):{$categoryName}([^\|\]]*)(\|[^\|\]]*)?\]\]";
		$cleanText = '';

		// Finally rename the category itself
		$mover = new MovePage( $category->getTitle(), Title::newFromText($newName, NS_CATEGORY) );
		if( !$mover->isValidMove() ) {
			return false;
		}
		$mover->move( $this->getUser(), 'moved by CategoryTools', false );

		// Reassign category members
		/** @var Title $p */
		foreach ($category->getMembers() as $p) {
			//if( $p->getNamespace() === NS_MAIN ) {
				// Cleanup the category markup
				$wp = WikiPage::newFromID($p->getArticleID());
				$pageText = $wp->getContent()->getWikitextForTransclusion();
				// Check linewise for category links:
				foreach ( explode( "\n", $pageText ) as $textLine ) {
					// Filter line through pattern and store the result:
					$cleanText .= preg_replace( "/{$pattern}/i", "[[{$categoryNamespace}:{$newName}]]", $textLine ) . "\n";
				}
				// Place the cleaned text into the text box:
				$cleanText = trim( $cleanText );
				$wp->doEditContent(new WikitextContent($cleanText), 'Renamed category by CategoryTools', EDIT_DEFER_UPDATES);
			//}
		}

		//WikiPage::newFromID( Title::newFromText($newName, NS_CATEGORY)->getArticleID() )->doPurge();

		wfGetDB(DB_MASTER)->commit();

		$this->formattedData = array('status' => 'success');

	}

	private function delete() {

		$categoryId = $this->parsedParams['id'];
		$this->deleteCategory($categoryId);

		$this->formattedData = array('status' => 'success');

	}

	private function deleteCategory($categoryId) {

		global $wgContLang;

		$category = Category::newFromTitle( Title::newFromID($categoryId) );
		$categoryName = $category->getTitle()->getText();

		if( !$category ) {
			return false;
		}

		$categoryNamespace = $wgContLang->getNsText( NS_CATEGORY );
		$pattern = "\[\[({$categoryNamespace}):{$categoryName}([^\|\]]*)(\|[^\|\]]*)?\]\]";
		$cleanText = '';

		// Delete category members
		/** @var Title $p */
		foreach ($category->getMembers() as $p) {
			if( $p->getNamespace() === NS_MAIN ) {
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
				$wp->doEditContent(new WikitextContent($cleanText), 'Removed category by CategoryTools', EDIT_DEFER_UPDATES);
				//$wp->doPurge();
			}
			// Delete sub-categories
			if( $p->getNamespace() == NS_CATEGORY ) {
				// And their pages
				$this->deleteCategory($p->getArticleID());
				Article::newFromID( $p->getArticleID() )->doDeleteArticle('deleted by CategoryTools');
			}
		}

		// Finally delete the category page
		Article::newFromID( $category->getTitle()->getArticleID() )->doDeleteArticle('deleted by CategoryTools');

		wfGetDB(DB_MASTER)->commit();

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
					$catItem['pages'][] = array(
						'title' => $member->getText(),
						'link' => $member->getFullURL()
					);
					continue;
				}
				$subCateMembers = array();

				foreach( Category::newFromTitle($member)->getMembers() as $m ) {
					$subCateMembers[] = array(
						'title' => $m->getText(),
						'link' => $m->getFullURL()
					);
				}

				$catItem['children'][] = array(
					'text' => $member->getText(),
					'id' => $member->getArticleID(),
					'children' => array(),
					'data' => array( 'url' => $member->getFullURL() ),
					'pages' => $subCateMembers
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