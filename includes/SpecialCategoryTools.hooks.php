<?php

class SpecialCategoryToolsHooks {

	/**
	 * Prevent people without permission create root categories (only sub-categories are allowed)
	 *
	 * @param Article $article
	 * @param User    $user
	 * @param string  $text
	 * @param string  $summary
	 * @param bool    $minor
	 * @param bool    $watchthis
	 * @param null    $sectionanchor
	 * @param int     $flags
	 * @param Status    $status
	 *
	 * @return bool
	 */
	public static function onArticleSave( &$article, &$user, &$text, &$summary, $minor, $watchthis, $sectionanchor, &$flags, &$status ) {

		global $wgContLang, $wgRestrictCategories;

		// Check if feature is enabled
		if( !$wgRestrictCategories ) {
			return true;
		}

		// Check namespace of the article being edited/created (we're interested only in categories)
		$ns = $article->getTitle()->getNamespace();
		if( $ns == NS_CATEGORY ) {

			// Test if we're creating a sub-category, condition is that page located in NS_CATEGORY namespace and contains a link to any other category
			$categoryNamespace = $wgContLang->getNsText( NS_CATEGORY );
			$pattern = "/\[\[({$categoryNamespace}):([^\|\]]*)(\|[^\|\]]*)?\]\]/";

			// If it's not a sub-category, try to prevent it
			if ( !preg_match($pattern, $text) ) {

				if( !$user->isAllowed('categorytools-create-categories') ) {
					// Prevent non-sysops (by default) and users without appropriate permission from creating categories pages
					$status = Status::newFatal(wfMessage('categorytools-action-not-allowed'));
					return false;
				}

			}
		}

		return true;

	}

	public static function onResourceLoaderGetConfigVars( &$vars ) {

		global $wgRestrictCategories;

		$vars['wgRestrictCategories'] = $wgRestrictCategories;

		return true;

	}

}