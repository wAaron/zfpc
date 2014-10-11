<?php
	/**
	 * The form of a user entrance.
	 *
	 * @author Kovalev Yury, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Opencart
	 * @version 1.0.0
	 */
	class Opencart_Form_Login extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction( $this->getView()->url() );

			// Username.
			$this->addElement( 'text', 'username', array (
				'label' => 'username',
				'required' => true
			) );
			// Password.
			$config = Zend_Registry::get( 'config' );
			$this->addElement( 'password', 'password', array (
				'label' => 'password',
				'required' => true,
				'description' => sprintf(
					$this->getTranslator()->_( 'forgot password link' ),
					$config->plugin->center->baseUrl . 'opencart/auth/forgot'
				)
			) );
			$this->getElement( 'password' )
				->getDecorator( 'description' )
				->setEscape( false )
			;
			// Buttons.
			$this->addElement( 'submit', 'login', array (
				'label' => 'login'
			) );
		}
	}
