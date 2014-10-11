<?php
	/**
	 * The form of a user editing.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Volusion
	 * @version 1.0.0
	 */
	class Volusion_Form_Edit extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction( $this->getView()->url(
					array (
						'module' => 'volusion',
						'controller' => 'auth',
						'action' => 'edit'
					), null, true
				) );
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
			// Shop.
			$this->addElement( 'text', 'shop', array (
				'label' => 'shop url',
				'disabled' => 'disabled',
			) );
			$this->getElement( 'shop' )->getDecorator( 'Label' )->setOption( 'escape', false );
			// Username.
			$this->addElement( 'text', 'username', array (
				'label' => 'username',
				'required' => true
			) );
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
				'label' => 'new password',
			) );
			// Confirm password.
			$this->addElement( 'password', 'confirm_password', array (
				'label' => 'confirm new password',
			) );
			// Buttons.
			$this->addElement( 'submit', 'save', array (
				'label' => 'save'
			) );
			$this->getElement( 'save' )->removeDecorator( 'DtDdWrapper' );
			$this->getElement( 'save' )->addDecorator( 'HtmlTag', array (
				'tag' => 'dt', 'class' => 'buttons', 'placement' => 'prepend', 'openOnly' => true
			) );
			$this->addElement( 'button', 'cancel', array (
				'label' => 'cancel',
				'onclick' => "location.href = '". Zend_Controller_Front::getInstance()->getRouter()->assemble( array (
					'module' => 'volusion',
					'controller' => 'auth',
					'action' => 'account',
					'plugin' => Zend_Controller_Front::getInstance()->getRequest()->getParam( 'plugin' )
				), false, 'default' ) ."';"
			) );
			$this->getElement( 'cancel' )->removeDecorator( 'DtDdWrapper' );
		}
	}
