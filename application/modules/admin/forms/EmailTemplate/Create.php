<?php
	/**
	 * Email template create form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.3
	 */
	class Admin_Form_EmailTemplate_Create extends Zend_Form
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
					'id' => 'template-create-form',
					'class' => 'form-horizontal'
				) );
			// Platform.
			$multiOptions = array ( '' => 'Default template' );
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
			// Type.
			$config = Config::getInstance();
			$multiOptions = $config->notification->templates->toArray();
			$multiOptions = array_merge(
				array ( '' => 'Choose template type' ),
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
			$multiOptions = array ( '' => 'No app' );
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
			// Subject.
			$this->addElement( 'text', 'subject', array (
				'label' => 'subject'
			) );
			// Content.
			$this->addElement( 'textarea', 'content', array (
				'label' => 'content'
			) );
		}
	}
