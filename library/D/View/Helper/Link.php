<?php
	require_once 'Zend/View/Helper/Abstract.php';

	/**
	 * HTML link helper.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.0
	 */
	class D_View_Helper_Link extends Zend_View_Helper_Abstract
	{
		/**
		 * Forms html link.
		 * @param string $langKey - lang file key.
		 * @param string $url - url for href attribute.
		 * @param array $attributes - other html attributes.
		 * @return string
		 */
		public function link( $langKey, $url, $attributes = null )
		{
			$link = '<a href="'. $url .'"';
			if ( $attributes ) {
				foreach ( $attributes as $name => $value ) {
					$link .= ' '. $name .'="'. $value .'"';
				}
			}
			$link .= '>'. Zend_Registry::get( 'translate' )->_( $langKey ) .'</a>';
			return $link;
		}
	}
