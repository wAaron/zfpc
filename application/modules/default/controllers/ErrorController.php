<?php
	/**
	 * Error controller. Handles erros and exceptions.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.3
	 */
	class Default_ErrorController extends Zend_Controller_Action
	{
		/**
		 * Error handler.
		 */
		public function errorAction()
		{
			$this->_helper->layout->disableLayout();
			$errors = $this->_getParam( 'error_handler' );
			if ( !$errors || !$errors instanceof ArrayObject ) {
				$this->view->message = 'You have reached the error page';
				return;
			}
			switch ( $errors->type )
			{
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
					// 404 error -- controller or action not found
					$this->getResponse()->setHttpResponseCode(404);
					$priority = Zend_Log::NOTICE;
					$this->view->message = 'Page not found';
					break;

				default:
					// application error
					$this->getResponse()->setHttpResponseCode(500);
					$priority = Zend_Log::CRIT;
					$this->view->message = 'Application error';
					break;
			}
			// Log exception.
			if ( $log = $this->getLog() ) {
				$log->log( $this->view->message, $priority, $errors->exception );
				$log->log( 'Request Parameters', $priority, json_encode( $errors->request->getParams() ) );
			}
			// Conditionally display exceptions.
			if ( $this->getInvokeArg('displayExceptions') == true ) {
				$this->view->exception = $errors->exception;
			}
			// View.
			$this->view->headTitle( $this->view->message );
			$this->view->request = $errors->request;
		}

		/**
		 * Returns error log.
		 * @return Zend_Log
		 */
		public function getLog()
		{
			$bootstrap = $this->getInvokeArg( 'bootstrap' );
			if ( !$bootstrap->hasResource( 'Log' ) ) {
				return false;
			}
			$log = $bootstrap->getResource( 'Log' );
			return $log;
		}
	}
