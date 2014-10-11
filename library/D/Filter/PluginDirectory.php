<?php
	/**
	 * Filter for plugin directory name.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.0
	 */
	class D_Filter_PluginDirectory implements Zend_Filter_Interface
	{
		/**
		 * @internal Overrided.
		 */
		public function filter( $pluginName )
		{
			$pluginName = preg_replace( '/[^\w]+/', '-', strtolower( $pluginName ) );
			$pluginName = preg_replace( '/[\-]+/', '-', strtolower( $pluginName ) );
	 		return $pluginName;
		}
	}
