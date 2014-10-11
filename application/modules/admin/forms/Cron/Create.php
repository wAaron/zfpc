<?php
	/**
	 * Cron create form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.2
	 */
	class Admin_Form_Cron_Create extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Default' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction(
					$this->getView()->url( array (
						'action' => 'create'
					) )
				)
				->setOptions( array (
					'id' => 'create-form',
					'class' => 'form-horizontal'
				) );
			// Name.
			$this->addElement( 'text', 'name', array (
				'label' => 'name',
				'required' => true,
				'validators' => array (
					array (
						'Alnum', true, array (
							'allowWhiteSpace' => true
						)
					)
				)
			) );
			// Key.
			$this->addElement( 'text', 'key', array (
				'label' => 'key',
				'required' => true,
				'validators' => array (
					array (
						'Regex', true, array (
							'pattern' => '/[\w\s\-_]+/'
						)
					)
				)
			) );
			// Description.
			$this->addElement( 'textarea', 'description', array (
				'label' => 'description'
			) );
			// Server.
			$multiOptions = array ();
			$servers = Table::_( 'servers' )->fetchAll();
			foreach ( $servers as $server ) {
				$multiOptions[ $server->id ] = $server->custom_name;
			}
			$this->addElement( 'select', 'server_id', array (
				'label' => 'server',
				'required' => true,
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Platform.
			$multiOptions = array (
				'' => 'System'
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multiOptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform_id', array (
				'label' => 'platform',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Plugin.
			$multiOptions = array ();
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multiOptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin_id', array (
				'label' => 'plugin',
				'disabled' => 'disabled',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Interval.
			$this->addElement( 'text', 'interval', array (
				'label' => $this->getTranslator()->_( 'interval' ) . ' ( sec )',
				'required' => true,
				'validators' => array (
					'Int'
				)
			) );
			// Max exec time.
			$this->addElement( 'text', 'max_exec_time', array (
				'label' => $this->getTranslator()->_( 'max exec time' ) . ' ( sec )',
				'required' => true,
				'validators' => array (
					'Int'
				)
			) );
			// launch file.
			$this->addElement( 'text', 'launch_file', array (
				'label' => 'launch file',
				'required' => true,
				'validators' => array (
					array (
						'Regex', true, array (
							'pattern' => '/[\/\w\s\.\-_]+/'
						)
					)
				)
			) );
			// Cron file.
			$this->addElement( 'text', 'cron_file', array (
				'label' => 'cron file',
				'required' => true,
				'validators' => array (
					array (
						'Regex', true, array (
							'pattern' => '/[\w\._\/]+/'
						)
					)
				)
			) );
		}
	}
