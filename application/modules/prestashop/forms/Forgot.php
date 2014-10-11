<?php
	/**
	 * The form for password restoring.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Prestashop
	 * @version 1.0.0
	 */
	class Prestashop_Form_Forgot extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction( $this->getView()->url(
					array (
						'module' => 'prestashop',
						'controller' => 'auth',
						'action' => 'forgot'
					), null, true
				) );
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
			// Username.
			$this->addElement( 'text', 'username', array (
				'id' => 'f-username',
				'label' => 'username',
				'required' => true
			) );
			// Buttons.
			$this->addElement( 'submit', 'login', array (
				'label' => 'get new password'
			) );
			$this->getElement( 'login' )->removeDecorator( 'DtDdWrapper' );
			$this->getElement( 'login' )->addDecorator( 'HtmlTag', array (
				'tag' => 'dt', 'class' => 'buttons', 'placement' => 'prepend', 'openOnly' => true
			) );
			$this->addElement( 'button', 'cancel_forgot', array (
				'label' => 'back to log in'
			) );
			$this->getElement( 'cancel_forgot' )->removeDecorator( 'DtDdWrapper' );
		}
	}
