<?php
	/**
	 * Filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_Form_Cron_Filter extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Filter' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction( $this->getView()->url() )
				->setOptions( array (
					'id' => 'filter-form'
				) );
			// Server.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all servers' )
			);
			$servers = Table::_( 'servers' )->fetchAll();
			foreach ( $servers as $server ) {
				$multioptions[ $server->id ] = $server->custom_name;
			}
			$this->addElement( 'select', 'server', array (
				'label' => 'server',
				'multioptions' => $multioptions
			) );
			// Platform.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all platforms' )
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multioptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform', array (
				'label' => 'platform',
				'multioptions' => $multioptions
			) );
			// Plugin.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all plugins' )
			);
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multioptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin', array (
				'label' => 'plugin',
				'multioptions' => $multioptions
			) );
		}
	}
