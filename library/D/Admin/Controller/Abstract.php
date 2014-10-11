<?php
	/**
	 * Admin abstract class.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.2.6
	 */
	abstract class D_Admin_Controller_Abstract extends Zend_Controller_Action
	{
		/**
		 * controller_action string as a resource name
		 * @var
		 */
		protected $_resourceName;

		/**
		 * Create form.
		 * @var Zend_Form
		 */
		protected $_createForm;

		/**
		 * Edit form.
		 * @var Zend_Form
		 */
		protected $_editForm;

		/**
		 * Class entity.
		 * @var string
		 */
		protected $_entity;

		/**
		 * Db table key.
		 * @var string
		 */
		protected $_table;

		/**
		 * Initialization.
		 */
		public function init()
		{
			$actionName = $this->getRequest()->getActionName();
			$controllerName = $this->getRequest()->getControllerName();
			// Check viewer.
			$viewer = Table::_( 'admins' )->getViewer();
			if ( !$viewer && ( $actionName != 'login' ) ) {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'login', 'index', 'admin' );
			}
			$viewerLevelId = $viewer ? $viewer->access_level : null;
			// Build access control object.
			$acl = $this->getHelper( 'Acl' )
				->buildAcl( $viewerLevelId );
			Zend_Registry::set( 'acl', $acl );
			if ( ( $actionName == 'access-denied' ) || ( $actionName == 'login' ) ) {
				return;
			}
			// Check current resource is allowed for viewer.
			$isAllowedController = $acl->isAllowed( $viewerLevelId, $controllerName );
			$isAllowedResource = $acl->isAllowed( $viewerLevelId, $controllerName .'_'. $actionName );
			$isAllowed = $isAllowedController && $isAllowedResource;
			if ( !$isAllowed ) {
				return $this->getHelper( 'Redirector' )
					->gotoSimple( 'access-denied', 'levels', 'admin' );
			} else {
				$this->_resourceName = $this->view->resourceName = $controllerName .'_'. $actionName;
			}
		}

		/**
		 * Standard create action.
		 */
		public function createAction()
		{
			// Process submitted data.
			if ( $this->_request->isPost() ) {
				if ( $this->_createForm->isValid( $this->_request->getPost() ) ) {
					$this->_createEntity();
					$this->view->message = Zend_Registry::get( 'translate' )->_(
						$this->_entity . ' has been successfully created'
					);
				}
			}
			// Prepare view.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->setScriptPath(
				ROOT_PATH . '/library/D/Admin/view/scripts/'
			);
			$this->view->form = $this->_createForm;
			$this->renderScript( 'form.phtml' );
		}

		/**
		 * Creates an entity.
		 */
		protected function _createEntity()
		{
			Table::_( $this->_table )->insert(
				$this->_createForm->getValues()
			);
		}

		/**
		 * Standard edit action.
		 */
		public function editAction()
		{
			// Check entity id.
			$id = $this->_getParam( 'id' );
			if ( !$this->_checkEntityId( $id ) ) {
				throw new Exception( 'Standard edit action received bad entity ID.' );
			}
			// Process submitted data.
			if ( $this->_request->isPost() ) {
				if ( $this->_editForm->isValid( $this->_request->getPost() ) ) {
					$this->_updateEntity( $id );
					$this->view->message = Zend_Registry::get( 'translate' )->_(
						$this->_entity . ' has been successfully updated'
					);
				}
			}
			// Fill a form with current values.
			else {
				$this->_setEntity( $id );
			}
			// Prepare view.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->setScriptPath(
				ROOT_PATH . '/library/D/Admin/view/scripts/'
			);
			$this->view->form = $this->_editForm;
			$this->renderScript( 'form.phtml' );
		}

		/**
		 * Checks entity's id.
		 * @param integer $id - entity's id.
		 * @return bool
		 */
		protected function _checkEntityId( $id ) {
			return ( $id && is_numeric( $id ) );
		}

		/**
		 * Sets an entity for editing.
		 * @param integer $id - entity's id.
		 */
		protected function _setEntity( $id )
		{
			$row = Table::_( $this->_table )->get( $id );
			$this->_editForm->populate( $row->toArray() );
			$this->_postPopulationModification();
		}

		/**
		 * Updates an entity.
		 * @param integer $id - entity's id.
		 */
		protected function _updateEntity( $id )
		{
			Table::_( $this->_table )->update(
				$this->_editForm->getValues(),
				array ( 'id = ?' => $id )
			);
		}

		/**
		 * Some particular data modification after a form population.
		 */
		protected function _postPopulationModification() {}

		/**
		 * Standard delete action.
		 */
		public function deleteAction()
		{
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Check entity id.
			$id = $this->_getParam( 'id' );
			if ( !$this->_checkEntityId( $id ) ) {
				throw new Exception( 'Standard delete action received bad entity ID.' );
			}
			// Delete entity.
			$this->_deleteEntity( $id );
		}

		/**
		 * Deletes an entity.
		 * @param integer $id - entity's id.
		 */
		protected function _deleteEntity( $id )
		{
			Table::_( $this->_table )->delete(
				array ( 'id = ?' => $id )
			);
		}

		/**
		 * Allows of downloading specific files, defined in the method.
		 */
		public function downloadAction()
		{
			$this->getHelper( 'Layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			$filter = new D_Filter_FileName();
			$name = $filter->filter( base64_decode(
				$this->_getParam( 'name' )
			) );
			switch ( $this->_getParam( 'type' ) ) {
				case 'mailchimp':
					$path = $config->mailchimp->directory .'/'. $name;
					$contentType = 'text/csv; charset=utf-8';
					break;
			}
			if ( isset ( $path ) && is_file( $path ) ) {
				$content = file_get_contents( $path );
				header( 'Content-Type: ' . $contentType );
				header( 'Content-Length: ' . strlen( $content ) );
				header( 'Content-Disposition: attachment; filename="'. $name .'"' );
				echo $content;
			}
		}

		/**
		 * Allows of removing specific files, defined in the method.
		 */
		public function removeAction()
		{
			$this->getHelper( 'Layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			$filter = new D_Filter_FileName();
			$name = $filter->filter( base64_decode(
				$this->_getParam( 'name' )
			) );
			switch ( $this->_getParam( 'type' ) ) {
				case 'mailchimp':
					$path = $config->mailchimp->directory .'/'. $name;
					break;
			}
			if ( isset ( $path ) && is_file( $path ) ) {
				unlink( $path );
			}
			$this->getHelper( 'Redirector' )
				->gotoUrl( $_SERVER['HTTP_REFERER'] );
		}
	}
