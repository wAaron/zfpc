<?php
	/**
	 * The form of a plugin installation.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Shopify
	 * @version 1.0.1
	 */
	class Shopify_Form_Install extends Zend_Form
	{
		public function init()
		{
			$view = Zend_Layout::getMvcInstance()->getView();
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction(
					$view->serverUrl(
						$view->url( array (
							'action' => 'install'
						) )
					)
				);
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
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
				'checked' => true,
				'label' => 'send me update news',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array ( 'placement' => 'append', 'escape' => false ) ),
					array ( 'HtmlTag', array ( 'tag' => 'dt', 'class' => 'news-subscription' ) )
				)
			) );
			$this->addElement( 'checkbox', 'product_news', array (
				'checked' => true,
				'label' => 'send me product news',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array ( 'placement' => 'append', 'escape' => false ) ),
					array ( 'HtmlTag', array ( 'tag' => 'dt', 'class' => 'news-subscription' ) )
				)
			) );
			// Buttons.
			$this->addElement( 'submit', 'install', array (
				'label' => 'install'
			) );
		}
	}
