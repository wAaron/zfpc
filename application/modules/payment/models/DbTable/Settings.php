<?php
	/**
	 * Payment settings db table.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.3.1
	 */
	class Payment_Model_DbTable_Settings extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_settings';

		/**
		 * Returns instance settings.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return stdClass
		 */
		public function getSettings( $shopId, $pluginId )
		{
			$rowset = $this->fetchAll(
				$this->select()
					->where( 'shop_id = ?', $shopId )
					->where( 'plugin_id = ?', $pluginId )
			);
			$settings = new stdClass();
			foreach ( $rowset as $row ) {
				$name = str_replace( ' ', '_', $row->name );
				$settings->{$name} = $row->value;
			}
			return $settings;
		}

		/**
		 * Returns instance setting.
		 * @param string $name - setting name.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function getSetting( $name, $shopId, $pluginId )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'name = ?', $name )
					->where( 'shop_id = ?', $shopId )
					->where( 'plugin_id = ?', $pluginId )
			);
		}

		/**
		 * Sets instance setting.
		 * @param string $name - setting name.
		 * @param mixed $value - setting value.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 */
		public function setSetting( $name, $shopId, $pluginId, $value )
		{
			return $this->update( array (
				'value' => $value,
			), array (
				'name = ?' => $name,
				'shop_id = ?' => $shopId,
				'plugin_id = ?' => $pluginId
			) );
		}
	}
