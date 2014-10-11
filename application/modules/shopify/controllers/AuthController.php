<?php
	/**
	 * Shopify user authentication.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 1.0.2
	 */
	class Shopify_AuthController extends D_Controller_InnerAuth_Auth
	{
		protected $_platform = 'Shopify';

		/**
		 * @internal Overrode
		 */
		public function init()
		{
			$pluginName = base64_decode( $this->_getParam( 'plugin' ) );
			// Validate an user.
			if ( in_array( $this->_request->getActionName(), array ( 'account' ) ) ) {
				if ( !$this->_user = $this->getHelper( $this->_platform )->validateUser( $pluginName ) ) {
					throw new Exception( 'No user was found.' );
				}
			}
			// View.
			$this->view->headTitle(
				$this->getHelper( 'Platform' )->headTitle( $this->_platform, $pluginName )
			);
			$this->view->platform = strtolower( $this->_platform );
			$this->view->setScriptPath(
				ROOT_PATH . '/library/D/Platform/InnerAuth/scripts/'
			);
		}

		/**
		 * @internal Overrode
		 */
		public function accountAction()
		{
			parent::accountAction();
			$this->view->setScriptPath(
				APPLICATION_PATH . '/modules/shopify/views/scripts/'
			);
		}

		/**
		 * Returns application details for given plugin.
		 * @internal It is CLI action.
		 * @internal [ plugin ] [ user ]
		 */
		public function credentialsAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Parameters.
			$pluginName = $this->_getParam( 'plugin' );
			// Load credentials.
			$modelPayment = new Payment_Model_Payment();
			$plugin = $modelPayment->getPlugin( strtolower( $this->_platform ), $pluginName );
			$details = Table::_( 'pluginDetails' )->fetchRow( "`plugin_id` = {$plugin->id}" )->toArray();
			echo json_encode( $details );
		}
	}
