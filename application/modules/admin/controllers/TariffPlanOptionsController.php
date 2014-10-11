<?php
	/**
	 * Tariff plan options controller.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_TariffPlanOptionsController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_createForm = new Admin_Form_TariffPlanOption_Create();
			$this->_editForm = new Admin_Form_TariffPlanOption_Edit();
			$this->_entity = 'option';
			$this->_table = 'options';
		}

		/**
		 * Option list.
		 */
		public function indexAction()
		{
			$options = Table::_( 'options' )->getAdminList();
			// Prepare view.
			$this->view->options = $options;
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'tariff plan options' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Checks whether an option can be deleted before it.
		 * @internal Overrode
		 */
		protected function _deleteEntity( $id )
		{
			$row = Table::_( 'plansToOptions' )->fetchRow( array (
				'option_id = ?' => $id
			) );
			if ( !$row ) {
				parent::_deleteEntity( $id );
			}
		}
	}
