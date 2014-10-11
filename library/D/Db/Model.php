<?php
	/**
	 * DbTable Model container.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Library
	 * @version 1.0.1
	 */
	class Model
	{
		/**
		 * Model 'key-class name' binds.
		 * @var array
		 */
		static private $_models = array (
			'settings' => 'Admin_Model_Settings',
			'payment' => 'Payment_Model_Payment',
		);

		private function __construct() {}

		/**
		 * Returns model object is bound with given key.
		 * @param string $key - model key.
		 * @return object
		 */
		static public function _( $key )
		{
			if ( array_key_exists( $key, self::$_models ) ) {
				if ( !Zend_Registry::isRegistered( 'model_' . $key ) ) {
					Zend_Registry::set( 'model_' . $key, new self::$_models[ $key ] );
				}
				return Zend_Registry::get( 'model_' . $key );
			}
		}
	}
