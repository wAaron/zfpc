<?php
/**
 * Email sender controller.
 * Provides sending of emails to customers.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.1
 */
class Admin_EmailsController extends D_Admin_Controller_Abstract
{
	/**
	 * Initialization.
	 */
	public function init()
	{
		parent::init();
		$this->_table = 'emails';
		$this->_editForm = new Admin_Form_Emails_Edit();
		$this->_entity = 'email';
	}

	/**
	 * emails list.
	 */
	public function indexAction()
	{
		$config = Config::getInstance();
		// Prepare filter params.
		$this->view->formFilter = $formFilter = new Admin_Form_Emails_Filter();
		$filterParams = array ();
		if ( $this->_request->isPost() ) {
			if ( $formFilter->isValid( $this->_request->getPost() ) ) {
				$filterParams = $formFilter->getValues();
				$this->view->filtered = true;
			}
		}
		// Load plans.
		$page = $this->_getParam( 'page', 1 );
		$emails = Table::_($this->_table)->getByParams($filterParams);
		$paginator = Zend_Paginator::factory( $emails );
		$paginator->setItemCountPerPage(
			$config->plugin->center->admin->itemsPerPage
		);
		$paginator->setCurrentPageNumber( $page );
		// Prepare view.
		$this->view->emails = $paginator;

		//set title
		$translate = Zend_Registry::get( 'translate' );
		$this->view->title = $translate->_( 'emails' );
		$this->view->headTitle( $this->view->title );

		//add viewer and his credentials to view
		$this->view->viewer = $viewer = Table::_('admins')->getViewer();
		$this->view->isSuperAdmin = $viewer->isSuperAdmin();
	}

	/**
	 * view email message
	 */
	public function viewAction()
	{
		$email = Table::_($this->_table)->getEmailById( $this->_getParam( 'id' ) );

		if($email['message']){
			$this->view->message = gzuncompress($email['message']);
			//$this->view->message = $email['message'];
		}
		Zend_Layout::getMvcInstance()->disableLayout();
	}
}
