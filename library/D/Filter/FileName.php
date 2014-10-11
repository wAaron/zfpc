<?php
	/**
	 * Filter for file name.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.0
	 */
	class D_Filter_FileName implements Zend_Filter_Interface
	{
		/**
		 * @internal Overrided.
		 */
		public function filter( $fileName )
		{
			$fileName = preg_replace( '/[^\w\s\.\-\(\)_]/', '_', $fileName );
			$fileName = preg_replace( '/[_]+/', '_', $fileName );
	 		return $fileName;
		}
	}
