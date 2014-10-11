<?php
	/**
	 * Cron db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Model_DbTable_Cron extends D_Db_Table_Abstract
	{
		protected $_name = 'cron';

		/**
		 * Returns cron task by key.
		 * @param string $key
		 * @return Zend_Db_Table_Row
		 */
		public function getByKey( $key )
		{
			return $this->fetchRow(
				$this->select()
					->where( '`key` = ?', $key )
			);
		}

		/**
		 * Returns cron tasks.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getTasks()
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'c' => $this->info( 'name' ) ),
						array ( '*' )
					)
					->joinLeft(
						array ( 's' => Table::_( 'servers' )->info( 'name' ) ),
						's.id = c.server_id',
						array ( 'server_name' => 'custom_name' )
					)
					->joinLeft(
						array ( 'plt' => Table::_( 'platforms' )->info( 'name' ) ),
						'plt.id = c.platform_id',
						array ( 'platform_name' => 'title' )
					)
					->joinLeft(
						array ( 'plg' => Table::_( 'plugins' )->info( 'name' ) ),
						'plg.id = c.plugin_id',
						array ( 'plugin_name' => 'name' )
					)
			);
		}
	}
