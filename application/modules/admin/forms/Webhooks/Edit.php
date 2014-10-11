<?php
	/**
	 * email edit form.
	 *
	 * @author Kuksanau Ihnat
	 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_Webhooks_Edit extends Zend_Form
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
						'action' => 'edit'
					) )
				)
				->setOptions( array (
					'id' => 'edit-form',
					'class' => 'form-horizontal'
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
                'multiOptions' => $multiOptions,
                'validators' => array (
                    'Int'
                )
            ) );

            // domain.
            $this->addElement( 'text', 'domain', array (
                'label' => 'Domain',
                'required' => true,
                'validators' => array (
                    new Zend_Validate_Hostname()
                )
            ) );

            // topic.
            $this->addElement( 'text', 'topic', array (
                'label' => 'Topic',
                'required' => true,
                'validators' => array (
                    new Zend_Validate_StringLength(array('max' => 50)),
                    new Zend_Validate_NotEmpty()

                )
            ) );

            // callback url.
            $this->addElement( 'text', 'callback_url', array (
                'label' => 'Callback Url',
                'required' => true,
                'validators' => array (
                    new Zend_Validate_StringLength(array('max' => 255)),
                    new Zend_Validate_NotEmpty()
                )
            ) );
		}
	}
