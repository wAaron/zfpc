<?php
	/**
	 * Db part of system configs.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Library
	 * @version 1.2.3
	 */
	class Config
	{
		/**
		 * Config instance.
		 * @var Config
		 */
		private static $_instance;

		/**
		 * Sign that object was constructed.
		 * @var bool
		 */
		private static $_constructed = false;

		/**
		 * Current field name.
		 * @var string
		 */
		private $_fieldName = null;

		/**
		 * Parent field reference.
		 * @var Config
		 */
		private $_parentField = null;

		/**
		 * Returns instance.
		 * @return Config
		 */
		public static function getInstance()
		{
			if ( !self::$_instance ) {
				self::$_instance = new Config();
			}
			return self::$_instance;
		}

		/**
		 * Constructor.
		 * Creates instance fields with values.
		 * Similar to Zend_Config.
		 */
		public function __construct()
		{
			if ( self::$_constructed ) return;
			self::$_constructed = true;
			$config = Table::_( 'config' )->fetchAll();
			foreach ( $config as $param ) {
				if ( !isset ( $this->{$param->section} ) ) {
					$this->{$param->section} = new Config();
					$this->{$param->section}->_fieldName = $param->section;
				}
				$lastField = &$this->{$param->section};
				$fields = explode( '.', $param['name'] );
				$lastFieldName = end( $fields );
				reset( $fields );
				foreach ( $fields as $field ) {
					if ( !property_exists( $lastField, $field ) ) {
						if ( $field != $lastFieldName ) {
							$lastField->{$field} = new Config();
							$lastField->{$field}->_fieldName = $field;
							$lastField->{$field}->_parentField = $lastField;
						} else {
							$value = $param['value'];
							$value = str_replace( 'ROOT_PATH', ROOT_PATH, $value );
							$value = str_replace( 'APPLICATION_PATH', APPLICATION_PATH, $value );
							$lastField->{$field} = $value;
							break;
						}
					}
					$lastField = &$lastField->{$field};
				}
			}
		}

		/**
		 * Returns parameter from ini file if it doesn't exist in db.
		 * @param string $name - requested parameter.
		 * @return Zend_Config_Ini
		 */
		public function __get( $name )
		{
			$config = Zend_Registry::get( 'config' );
			if ( $this->_fieldName ) {
				$fieldChain = $this->_fieldName;
				$currentField = $this;
				while ( $parent = $currentField->_getParent() ) {
					$fieldChain = $parent->_fieldName .'->'. $fieldChain;
					$currentField = $parent;
				}
				eval ( '$value = $config->'. $fieldChain .'->'. $name .';' );
				return $value;
			} else {
				return $config->{$name};
			}
		}

		/**
		 * Returns parent field.
		 */
		private function _getParent() {
			return $this->_parentField;
		}

		/**
		 * Returns object fields.
		 * @param object $object
		 * @return array
		 */
		public function toArray()
		{
			$vars = get_object_vars( $this );
			unset ( $vars['_fieldName'], $vars['_parentField'] );
			return $vars;
		}
	}
