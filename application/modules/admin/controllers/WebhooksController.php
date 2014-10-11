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
class Admin_WebhooksController extends D_Admin_Controller_Abstract
{
    /**
     * Initialization.
     */
    public function init()
    {
        parent::init();
        //init items
        $this->_table = 'webhooks';
        $this->_editForm = new Admin_Form_Webhooks_Edit();
        $this->_entity = 'webhook';
    }

    /**
     * webhooks list.
     */
    public function indexAction()
    {
		$config = Config::getInstance();
		// Prepare filter params.
		$this->view->formFilter = $formFilter = new Admin_Form_Webhooks_Filter();
		$filterParams = array ();
		if ( $this->_request->isPost() && $formFilter->isValid( $this->_request->getPost() )) {
			$filterParams = $formFilter->getValues();
			$this->view->filtered = true;
		}
		// Load webhooks.
		$page = $this->_getParam( 'page', 1 );
		$webhooks = Table::_($this->_table)->getByParams($filterParams);
		$paginator = Zend_Paginator::factory( $webhooks );
		$paginator->setItemCountPerPage(
			$config->plugin->center->admin->itemsPerPage
		);
		$paginator->setCurrentPageNumber( $page );
		// Prepare view.
		$this->view->webhooks = $paginator;
		//set title
        $translate = Zend_Registry::get( 'translate' );
        $this->view->title = $translate->_( 'external webhooks' );
        $this->view->headTitle( $this->view->title );
        $this->view->platformPlugins = $this->getHelper( 'admin' )
            ->getPlatformPlugins();

        //add viewer and his credentials to view
        $this->view->viewer = $viewer = Table::_('admins')->getViewer();
        $this->view->isSuperAdmin = $viewer->isSuperAdmin();
    }
}
