<?php

class CategoryToolsAPI extends ApiBase {

	private $parsedParams = array();
	private $formattedData = array();

	public function execute() {
		$this->parsedParams = $this->extractRequestParams();
		$method = $this->parsedParams['method'];
		switch ($method) {
			case 'rename':
				// TODO: ..
				break;
			case 'delete':
				// TODO: ..
				break;
			case 'read':
				$this->read();
				break;
		}
		//$this->getResult()->addValue(null,null, $this->formattedData);
		die( json_encode($this->formattedData) );
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