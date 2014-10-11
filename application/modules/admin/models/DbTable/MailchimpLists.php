<?php
	/**
	 * Mailchimp lists db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Model_DbTable_MailchimpLists extends D_Db_Table_Abstract
	{
		protected $_name = 'mailchimp_lists';

		/**
		 * Returns mailchimp list id.
		 * @param integer $platformId - platform id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Row
		 */
		public function getListId( $platformId, $pluginId )
		{
			$select = $this->select();
			if ( $platformId ) {
				$select->where( 'platform_id = ?', $platformId );
			}
			if ( $pluginId ) {
				$select->where( 'plugin_id = ?', $pluginId );
			}
			return $this->fetchRow( $select )->list_id;
		}
	}
