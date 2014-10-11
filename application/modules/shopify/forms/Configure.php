<?php
	/**
	 * The form of a plugin configuration.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 1.0.1
	 */
	class Shopify_Form_Configure extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction(
					Zend_Controller_Front::getInstance()->getRouter()->assemble( array (
						'action' => 'configure'
					) )
				);
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
			// Api id.
			$this->addElement( 'hidden', 'id' );
			$this->id->removeDecorator( 'label' );
			// Api key.
			$this->addElement( 'hidden', 'api_key' );
			$this->api_key->removeDecorator( 'label' );
			// Api user.
			$this->addElement( 'hidden', 'api_user' );
			$this->api_user->removeDecorator( 'label' );
			// Shop domain.
			$this->addElement( 'hidden', 'shop_domain' );
			$this->shop_domain->removeDecorator( 'label' );
			// Tariff plan.
			$multiOptions = array ();
			$this->addElement( 'select', 'plan_id', array (
				'label' => 'tariff plan',
				'required' => true,
				'MultiOptions' => $multiOptions
			) );
			// News.
			$this->addElement( 'checkbox', 'update_news', array (
				'label' => 'send me update news',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array ( 'placement' => 'append', 'escape' => false ) ),
					array ( 'HtmlTag', array ( 'tag' => 'dt', 'class' => 'news-subscription' ) )
				)
			) );
			$this->addElement( 'checkbox', 'product_news', array (
				'label' => 'send me product news',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array ( 'placement' => 'append', 'escape' => false ) ),
					array ( 'HtmlTag', array ( 'tag' => 'dt', 'class' => 'news-subscription' ) )
				)
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
					'module' => 'shopify',
					'controller' => 'auth',
					'action' => 'account',
					'plugin' => Zend_Controller_Front::getInstance()->getRequest()->getParam( 'plugin' )
				), false, 'default' ) ."';"
			) );
			$this->getElement( 'cancel' )->removeDecorator( 'DtDdWrapper' );
		}
	}
