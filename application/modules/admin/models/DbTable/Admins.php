<?php
	/**
	 * Admins db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Model_DbTable_Admins extends D_Db_Table_Abstract
	{
		protected $_name = 'admins';

		protected $_rowClass = 'Admin_Model_DbRow_Admin';

		/**
		 * model of current viewer
		 * @var Admin_Model_DbRow_Admin
		 */
		protected $_viewer = null;

		/**
		 * Returns an admin by nickname.
		 * @param string $nickname
		 * @return Zend_Db_Table_Row
		 */
		public function getAdmin( $nickname )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'nickname = ?', $nickname )
			);
		}

		/**
		 * Returns an admin by id
		 * @param $id
		 * @return null|Zend_Db_Table_Row_Abstract
		 */
		public function getAdminById($id)
		{
			return $this->fetchRow(
				$this->select()
					->where( 'id = ?', $id )
			);
		}

		/**
		 * returns list of all admins with their access level
		 * @return Zend_Db_Table_Rowset_Abstract
		 */
		public function getAllAdmins()
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'a' => $this->info( 'name' ) ),
						array ( '*' )
					)
					->joinLeft(
						array ( 'l' => Table::_( 'levels' )->info( 'name' ) ),
						'a.access_level = l.id',
						array ( 'level_name' => 'l.name' )
					)
			);
		}


		/** get model of current viewer
		 * @return Admin_Model_DbRow_Admin|null
		 */
		public function getViewer()
		{
			$result = null;
			//if viewer has been set
			if($this->_viewer){
				$result = $this->_viewer;
			}
			else{
				//if viewer exists
				$session = new Zend_Session_Namespace( 'pc.admin' );
				if($session->admin instanceof $this->_rowClass){
					$result = $session->admin;
					$this->setViewer($session->admin);
				}
			}

			return $result;
		}

		/**
		 * Set model of viewer to var of class
		 * @param $admin
		 */
		public function setViewer($admin)
		{
			if($admin instanceof Admin_Model_DbRow_Admin){
				Zend_Registry::set( 'viewer', $admin );
				$this->_viewer = $admin;
			}
		}
	}
