<?php
	/**
	 * The form of a plugin installation.
	 *
	 * @author Kovalev Yury, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Prestashop
	 * @version 1.0.1
	 */
	class Prestashop_Form_Install extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator( Zend_Registry::get( 'translate' ) )
				->setAction(
					Zend_Controller_Front::getInstance()->getRouter()->assemble( array (
						'action' => 'install'
					) )
				);
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
			// Shop domain.
			$this->addElement( 'hidden', 'shop_domain' );
			$this->shop_domain->removeDecorator( 'label' );
			// Api user.
			$config = Config::getInstance();
			$this->addElement( 'hidden', 'api_user', array (
				'label' => 'api user',
				'value' => 'admin'
			) );
			$this->getElement( 'api_user' )->removeDecorator( 'Label' );
			// Api token.
			$this->addElement( 'text', 'api_key', array (
				'label' => 'api token',
				'required' => true
			) );
			$this->getElement( 'api_key' )->getDecorator( 'Label' )->setOption( 'escape', false );
			// Shop view name.
			$this->addElement( 'text', 'store_view', array(
				'label' => 'Store Name <span>( if you use multistore <a href="'. $config->plugin->center->baseUrl .'default/index/page/prestashop/store" target="_blank">Click here</a> to find how to get it )</span>',
			) );
			$this->getElement( 'store_view' )->getDecorator( 'Label' )->setOption( 'escape', false );
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
