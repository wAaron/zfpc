<?php
	/**
	 * The form of a user registration.
	 *
	 * @author SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Magento
	 * @version 1.0.2
	 */
	class Magento_Form_Register extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction( $this->getView()->url() );
			// Shop.
			$this->addElement( 'text', 'shop', array (
				'label' => 'shop',
				'required' => true,
				'filters' => array (
					array ( 'filter' => 'PregReplace', 'options' =>  array (
						'match' => '/(?:(http|https):\/\/)*(?:www\.)*([\w\.\/]+)*/i',
						'replace' => '$2'
					) )
				)
			) );
			// Username.
			$this->addElement( 'text', 'username', array (
				'label' => 'username register',
				'required' => true
			) );
			$this->getElement( 'username' )
				->getDecorator( 'Label' )
				->setOption( 'escape', false )
				;
			// Email.
			$this->addElement( 'text', 'email', array (
				'label' => 'email',
				'required' => true,
				'validators' => array (
					'EmailAddress'
				)
			) );
			// Password.
			$this->addElement( 'password', 'password', array (
				'label' => 'password',
				'required' => true
			) );
			// Confirm password.
			$this->addElement( 'password', 'confirm_password', array (
				'label' => 'confirm password',
				'required' => true
			) );
			// Terms and conditions.
			$config = Config::getInstance();
			$this->addElement( 'checkbox', 'terms', array ( // TODO lang
				'label' => 'I have read and agreed with <a href="'. $config->plugin->center->baseUrl .'default/index/page/magento/terms" target="_blank">Terms of use</a>',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array ( 'placement' => 'append', 'escape' => false ) ),
					array ( 'HtmlTag', array ( 'tag' => 'dt', 'class' => 'terms' ) )
				)
			) );
			// Buttons.
			$this->addElement( 'submit', 'register', array (
				'label' => 'register'
			) );
		}
	}
