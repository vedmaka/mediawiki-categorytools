<?php

class CategoryTools {

	/**
	 * Fetches list of available categories in wiki
	 *
	 * @return Category[]
	 */
	public static function getAllCategories()
	{
		$categories = array();
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select(
			array( 'page', 'categorylinks' ),
			'*',
			array(
				'page_namespace' => NS_CATEGORY,
				'page_is_redirect' => 0,
				'cl_from IS NULL'
			),
			__METHOD__,
			array(),
			array(
				'categorylinks' => array( 'LEFT JOIN', 'cl_from = page_id' )
			)
		);
		while( $row = $result->fetchRow() ) {
			$categories[] = Category::newFromTitle( Title::newFromID($row['page_id']) );
		}
		return $categories;
	}

}