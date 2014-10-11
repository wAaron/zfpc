<?php
	/**
	 * Variables db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.2
	 */
	class Default_Model_DbTable_Variables extends D_Db_Table_Abstract
	{
		protected $_name = 'variables';

		/**
		 * Returns some value for some entity.
		 * Can decode response into JSON format.
		 *
		 * @param string $name - variable name.
		 * @param integer $externalId - entity id.
		 * @param bool $decode - whether to decode response or not.
		 * @return mixed
		 */
		public function get( $name, $externalId = null, $decode = false )
		{
			$select = $this->select()
				->where( 'name = ?', $name )
				;
			if ( $externalId ) {
				$select->where( 'external_id = ?', $externalId );
			}
			if ( $result = $this->fetchRow( $select ) ) {
				return ( $decode ? json_decode( $result->value ) : $result->value );
			}
			return null;
		}

		/**
		 * Sets some value for some entity.
		 * @param string $name - variable name.
		 * @param mixed $value - variable value.
		 * @param integer $externalId - entity id.
		 * @param bool $decode - whether to encode response or not.
		 * @return mixed
		 */
		public function set( $name, $value, $externalId = null, $encode = false )
		{
			$value = ( $encode ? json_encode( $value ) : $value );
			if ( $this->get( $name, $externalId ) ) {
				$where = "name = '$name'";
				if ( $externalId ) {
					$where .= " AND external_id = '$externalId'";
				}
				return $this->update( array (
					'value' => $value
				), $where );
			}
			else {
				return $this->insert( array (
					'external_id' => $externalId,
					'name' => $name,
					'value' => $value
				) );
			}
		}
	}
