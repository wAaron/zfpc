<?php
	/**
	 * Email templates controller.
	 * Provides with editing of template files.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.8
	 */
	class Admin_EmailTemplatesController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_createForm = new Admin_Form_EmailTemplate_Create();
			$this->_editForm = new Admin_Form_EmailTemplate_Edit();
			$this->_entity = 'template';
		}

		/**
		 * Template list.
		 */
		public function indexAction()
		{
			$config = Config::getInstance();
			$translate = Zend_Registry::get( 'translate' );
			// Files.
			$files = array ();
			$templates = $config->notification->templates->toArray();
			$libPluginDirectory = $config->notification->libPluginDirectory;
			$indexDirectory = $config->notification->indexDirectory;
			$optionsDirectory = $config->notification->optionsDirectory;
			$authDirectory = $config->notification->authDirectory;
			$scan = array_merge(
				scandir( $libPluginDirectory ),
				scandir( $indexDirectory ),
				scandir( $optionsDirectory ),
				scandir( $authDirectory )
			);
			foreach ( $scan as $file ) {
				if ( substr( $file, 0, 1 ) != '.' ) {
					$name = $file;
					$file = explode( '__', $file );
					$type = str_replace( '.phtml', '', array_pop( $file ) );
					if ( empty ( $file ) ) {
						$deleteButton = false;
						$platform = $translate->_( 'default' );
					} else {
						$deleteButton = true;
						$platform = ucfirst( array_pop( $file ) );
					}
					$plugin = empty ( $file ) ? '' :
						ucwords( str_replace( '_', ' ', array_pop( $file ) ) );
					$files[] = array (
						'name' => $name,
						'type' => $templates[ $type ],
						'platform' => $platform,
						'plugin' => $plugin,
						'deleteButton' => $deleteButton
					);
				}
			}
			// Prepare view.
			$this->view->files = $files;
			$this->view->formFilter = new Admin_Form_EmailTemplate_Filter();
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = $translate->_( 'email templates' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Forms template name and writes a file on a disc.
		 * @internal Overrode
		 */
		protected function _createEntity()
		{
			// Form template name.
			$separator = '__';
			$template = $type = $this->_createForm->type->getValue();
			$subject = $this->_createForm->subject->getValue();
			if ( $platformId = $this->_createForm->platform_id->getValue() ) {
				$platform = Table::_( 'platforms' )->get( $platformId );
				$template = $platform->name . $separator . $template;
			}
			if ( $pluginId = $this->_createForm->plugin_id->getValue() ) {
				$filter = new D_Filter_PluginDirectory();
				$plugin = Table::_( 'plugins' )->get( $pluginId );
				$template = str_replace( '-', '_', $filter->filter( $plugin->name ) ) . $separator . $template;
			}
			$template .= '.phtml';
			// Write a file.
			$directory = $this->_getTypeDirectory( $type );
			if ( !file_exists( $directory . $template ) ) {
				file_put_contents(
					$directory . $template,
					$this->_createForm->content->getValue()
				);
			}
			// Save a subject.
			$settingId = Model::_( 'settings' )->setting( $template . '[email subject]' );
			Model::_( 'settings' )->string( $settingId, $subject );
		}

		/**
		 * @internal Overrode
		 */
		protected function _checkEntityId( $id ) {
			$validator = new Zend_Validate_Regex( '/[\w\._]+/i' );
			return $validator->isValid( $id );
		}

		/**
		 * @internal Overrode
		 */
		protected function _setEntity( $id )
		{
			$data = explode( '__', $id );
			// Type.
			$template = array_pop( $data );
			$type = str_replace( '.phtml', '', $template );
			// Platform.
			$platformId = null;
			if ( !empty ( $data ) ) {
				$platform = array_pop( $data );
				$platform = Table::_( 'platforms' )->get( $platform );
				$platformId = $platform->id;
			}
			// Plugin.
			$pluginId = null;
			if ( !empty ( $data ) ) {
				$bunch = array (
					'memberships_discount_cards' => 'Memberships/Discount Cards'
				);
				$pluginName = array_pop( $data );
				if ( in_array( $pluginName, array_keys( $bunch ) ) ) {
					$pluginName = $bunch[ $pluginName ];
				} else {
					$pluginName = str_replace( '_', ' ', $pluginName );
				}
				$plugin = Model::_( 'payment' )->getPlugin( $platform->name, $pluginName );
				$pluginId = $plugin->id;
			}
			// Subject.
			$settingId = Model::_( 'settings' )->setting( $id . '[email subject]' );
			$subject = Model::_( 'settings' )->getValue( $settingId, 'string' );
			// Content.
			$directory = $this->_getTypeDirectory( $type );
			$content = file_get_contents( $directory . $id );
			// Fill a form.
			$this->_editForm->populate( array (
				'platform_id' => $platformId,
				'plugin_id' => $pluginId,
				'type' => $type,
				'subject' => $subject,
				'content' => $content
			) );
		}

		/**
		 * Rewrites an existing file.
		 * @internal Overrode
		 */
		protected function _updateEntity( $id )
		{
			$data = explode( '__', $id );
			$template = array_pop( $data );
			$type = str_replace( '.phtml', '', $template );
			$platformId = $this->_editForm->platform_id->getValue();
			$pluginId = $this->_editForm->plugin_id->getValue();
			$subject = $this->_editForm->subject->getValue();
			// Rewrite a file.
			$directory = $this->_getTypeDirectory( $type );
			$content = $this->_editForm->content->getValue();
			if ( is_file( $directory . $id ) ) {
				file_put_contents( $directory . $id, $content );
			}
			// Save a subject.
			$settingId = Model::_( 'settings' )->setting( $id . '[email subject]' );
			Model::_( 'settings' )->string( $settingId, $subject );
		}

		/**
		 * Deletes an existing file except base type.
		 * @todo setting removing.
		 * @internal Overrode
		 */
		protected function _deleteEntity( $id )
		{
			$data = explode( '__', $id );
			$template = array_pop( $data );
			$type = str_replace( '.phtml', '', $template );
			// Delete a file.
			if ( count( $data ) > 1 ) {
				$directory = $this->_getTypeDirectory( $type );
				if ( is_file( $directory . $id ) ) {
					unlink( $directory . $id );
				}
			}
		}

		/**
		 * Returns template directory by its type.
		 * @param string $type - template type.
		 * @return string
		 */
		private function _getTypeDirectory( $type )
		{
			$config = Config::getInstance();
			$directory = null;
			switch ( $type ) {
				case 'installed':
				case 'uninstalled':
					$directory = $config->notification->libPluginDirectory;
					break;
				case 'recently_installed':
				case 'run_out':
				case 'expired':
				case 'trial_run_out':
				case 'trial_expired':
					$directory = $config->notification->indexDirectory;
					break;
				case 'current_month_overdraft':
				case 'expired_overdraft':
				case 'unpaid_overdraft':
					$directory = $config->notification->optionsDirectory;
					break;
				case 'forgot':
					$directory = $config->notification->authDirectory;
					break;
			}
			return $directory;
		}
	}
