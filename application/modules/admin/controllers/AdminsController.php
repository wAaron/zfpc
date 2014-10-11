<?php
	/**
	 * Plugin Center admins.
	 *
	 * @author Kuksanau Ihnat
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_AdminsController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_createForm = new Admin_Form_Admin_Create();
			$this->_editForm = new Admin_Form_Admin_Edit();
			$this->_entity = 'admin';
			$this->_table = 'admins';
		}

		/**
		 * get all admins
		 */
		public function indexAction()
		{
			//set title
			$translate = Zend_Registry::get( 'translate' );
			$this->view->title = $translate->_( 'admins' );
			$this->view->headTitle( $this->view->title );

			//get admins list
			$this->view->admins = $admins = Table::_($this->_table)->getAllAdmins();
			$this->view->formFilter = new Admin_Form_Admin_Filter();

			//add viewer and his credentials to view
			$this->view->viewer = $viewer = Table::_($this->_table)->getViewer();
			$this->view->isSuperAdmin = $viewer->isSuperAdmin();
		}

		/**
		 * prepare data before save
		 */
		protected function _createEntity()
		{
			$config = Config::getInstance();
			$this->_createForm->removeElement('password_confirm');
			$this->_createForm->password->setValue(
				mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $this->_createForm->getValue('password'), MCRYPT_MODE_ECB )
			);

			parent::_createEntity();
		}

		/**
		 * preparations before update
		 * @param int $id
		 */
		protected function _updateEntity($id)
		{
			$row = Table::_( $this->_table )->get( $id );
			$isValid = true;

			//restore disabled fields of form
			if(!$this->_editForm->getValue('access_level')){
				$this->_editForm->access_level->setValue($row->access_level);
			}

			//we should remove db_exists validator if login has been stayed as previous
			$nickname = $this->_editForm->getValue('nickname');
			if($nickname != $row->nickname){
				$this->_editForm->getElement('nickname')->addValidator(
					'Db_NoRecordExists', false,	array(
						'table'     => 'admins',
						'field'     => 'nickname',
					)
				);
				if( !$this->_editForm->nickname->isValid( $nickname ) ){
					$isValid = false;
				}
			}

			//check password is changed
			if($this->_editForm->getValue('password')){
				$config = Config::getInstance();
				$this->_editForm->password->setValue(
					mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $this->_editForm->getValue('password'), MCRYPT_MODE_ECB )
				);
			}
			$this->_editForm->removeElement('password_confirm');

			if($isValid){
				parent::_updateEntity($id);
			}
		}

		/**
		 * check admin before delete
		 * @param int $id
		 */
		protected function _deleteEntity($id)
		{
			$admin = Table::_($this->_table)->getAdminById($id);
			//superadmins can not be deleted
			if(!$admin->isSuperAdmin()){
				parent::_deleteEntity($id);
			}
		}
	}
