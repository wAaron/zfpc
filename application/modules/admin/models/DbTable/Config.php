<?php
	/**
	 * Config db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_Model_DbTable_Config extends D_Db_Table_Abstract
	{
		protected $_name = 'config';

		/**
		 * Returns a list for admin side.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getAdminList()
		{
			return $this->fetchAll(
				$this->select()
					->order( 'section' )
			);
		}
	}
