<?php
	/**
	 * Plugin Center settings.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.1.3
	 */
	class Admin_SettingsController extends D_Admin_Controller_Abstract
	{
		/**
		 * General settings.
		 */
		public function generalAction()
		{
			// Process submitted data.
			if ( $this->_request->isPost() ) {
				foreach ( $this->_getParam( 'config' ) as $id => $value ) {
					if ( !empty ( $value ) ) {
						$value = htmlspecialchars_decode( $value );
						Table::_( 'config' )->update( array (
							'value' => $value
						), array (
							'id = ?' => $id,
							'value != ?' => $value
						) );
						$this->view->saved = true;
					}
				}
			}
			$configs = Table::_( 'config' )->getAdminList();
			// Prepare view.
			$this->view->configs = $configs;
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'general settings' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Email settings.
		 */
		public function emailAction()
		{
			// Process submitted data.
			if ( $this->_request->isPost() ) {
				$keys = array_merge(
					array_keys( $this->_request->getParam( 'installation' ) ),
					array_keys( $this->_request->getParam( 'uninstallation' ) ),
					array_keys( $this->_request->getParam( 'post_installation' ) ),
					array_keys( $this->_request->getParam( 'run_out_expired' ) ),
					array_keys( $this->_request->getParam( 'option_overdraft' ) )
				);
				$values = array_merge(
					array_values( $this->_request->getParam( 'installation' ) ),
					array_values( $this->_request->getParam( 'uninstallation' ) ),
					array_values( $this->_request->getParam( 'post_installation' ) ),
					array_values( $this->_request->getParam( 'run_out_expired' ) ),
					array_values( $this->_request->getParam( 'option_overdraft' ) )
				);
				$params = array_combine( $keys, $values );
				if ( !empty ( $params ) ) {
					foreach ( $params as $id => $value ) {
						Model::_( 'settings' )->bool( $id, $value );
					}
					$this->view->saved = true;
				}
			}
			// Prepare view.
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'email settings' );
			$this->view->headTitle( $this->view->title );
			$this->view->bunches = array (
				'installation' => Model::_( 'settings' )->getBunch( 'installation' ),
				'uninstallation' => Model::_( 'settings' )->getBunch( 'uninstallation' ),
				'post_installation' => Model::_( 'settings' )->getBunch( 'post installation' ),
				'run_out_expired' => Model::_( 'settings' )->getBunch( 'run out expired' ),
				'option_overdraft' => Model::_( 'settings' )->getBunch( 'option overdraft' )
			);
			$this->view->labels = array (
				'installation' => 'send installation email',
				'uninstallation' => 'send uninstallation email',
				'post_installation' => 'send post-installation email',
				'run_out_expired' => 'send run out of expired email',
				'option_overdraft' => 'send option overdraft email'
			);
		}
	}
