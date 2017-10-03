<?php

class SpecialCategoryTools extends SpecialPage {

	private $templater;

	public function __construct() {
		parent::__construct( 'CategoryTools', 'delete');
		$this->templater = new TemplateParser( dirname(__FILE__).'/../templates/' , true);
	}

	public function getGroupName() {
		return 'other';
	}

	public function execute( $subPage ) {

		$this->getOutput()->setPageTitle( wfMessage('categorytools-page-title') );
		$this->getOutput()->addModuleStyles('ext.categoryTools.jstree');
		$this->getOutput()->addModules('ext.categoryTools.jstree');
		$this->getOutput()->addModuleStyles('ext.categoryTools.main');
		$this->getOutput()->addModules('ext.categoryTools.main');

		$categoriesToRender = array();
		$categories = CategoryTools::getAllCategories();
		foreach ($categories as $category) {
			$catItem['title'] = $category->getName();
			$catItem['members'] = array();
			$members = $category->getMembers();
			/** @var Title $member */
			foreach ($members as $member) {
				if( $member->getNamespace() != NS_CATEGORY ) {
					continue;
				}
				$catItem['members'][] = $member->getBaseText();
			}
			$categoriesToRender[] = $catItem;
		}

		$html = $this->templater->processTemplate( 'categorytools', array(
			'messages' => array(
				'categorytools-page-description' => wfMessage('categorytools-page-description')->text()
			),
			'categories' => $categoriesToRender
		));

		$this->getOutput()->addHTML( $html );

	}

}