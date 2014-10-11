<?php
	/**
	 * Base DbTable class.
	 * Contains common methods.
	 *
	 * @todo admin list action fior common listing.
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.1.3
	 */
	abstract class D_Db_Table_Abstract extends Zend_Db_Table_Abstract
	{
		protected $_primary = 'id';

		/**
		 * Returns a record by identity.
		 * @param mixed $identity - value of id or name field.
		 * @return Zend_Db_Table_Row
		 */
		public function get( $identity )
		{
			if ( is_numeric( $identity ) ) {
				$where = "`id` = $identity";
			}
			else if ( is_string( $identity ) && !empty ( $identity ) ) {
				$where = '`name` = '. $this->getAdapter()
					->quote( $identity, 'string' );
			}
			else {
				return false; // TODO false -> null
			}
			return $this->fetchRow( $where );
		}

		/**
		 * Returns filed enum options.
		 * @param string $field - field name.
		 * @return array
		 */
		public function getEnumOptions( $field )
		{
			$options = array ();
			$metadata = $this->info( 'metadata' );
			$found = preg_match_all( '/\'(\w+)\'/', $metadata[ $field ]['DATA_TYPE'], $options );
			$options = $found ? $options[1] : $options;
			$enumOptions = array();
			foreach($options as $option)
			{
				$enumOptions[$option] = $option;
			}
			return $enumOptions;
		}
	}
