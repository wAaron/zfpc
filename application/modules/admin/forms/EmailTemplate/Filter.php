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
	class Admin_Form_EmailTemplate_Filter extends Zend_Form
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
			// Platform.
			$multiOptions = array (
				'' => $this->getTranslator()->_( 'all templates' ),
				'default' => $this->getTranslator()->_( 'default' )
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multiOptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform', array (
				'label' => 'platform',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Type.
			$config = Config::getInstance();
			$multiOptions = $config->notification->templates->toArray();
			$multiOptions = array_merge(
				array ( '' => $this->getTranslator()->_( 'all types' ) ),
				$multiOptions
			);
			$this->addElement( 'select', 'type', array (
				'label' => 'type',
				'required' => true,
				'multiOptions' => $multiOptions,
				'validators' => array (
					array (
						'Regex', true, array (
							'pattern' => '/[\w\s\-_]+/'
						)
					)
				)
			) );
			// Plugin.
			$multiOptions = array ( '' => $this->getTranslator()->_( 'all plugins' ) );
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multiOptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin', array (
				'label' => 'plugin',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
		}
	}
