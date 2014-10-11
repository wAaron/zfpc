<?php
	/**
	 * Plugin details db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 2.0.0
	 */
	class Default_Model_DbTable_PluginDetails extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_details';

		protected $_primary = 'plugin_id';

		protected $_rowClass = 'Default_Model_DbRow_PluginDetail';

		/**
		 * Returns plugin credentials.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function get( $pluginId )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'plugin_id = ?', $pluginId )
					->limit( '1' )
			);
		}
	}
