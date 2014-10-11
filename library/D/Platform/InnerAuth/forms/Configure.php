<?php
	/**
	 * The form of a plugin configuration.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.0.3
	 */
	class D_Form_InnerAuth_Configure extends Zend_Form
	{
		public function init()
		{
			$this->setTranslator(
					Zend_Registry::get( 'translate' )
				)->setAction(
					Zend_Controller_Front::getInstance()
						->getRouter()->assemble( array (
							'action' => 'configure'
						) )
				);
			// Plugin.
			$this->addElement( 'hidden', 'plugin' );
			$this->plugin->removeDecorator( 'label' );
			// Api id.
			$this->addElement( 'hidden', 'id' );
			$this->id->removeDecorator( 'label' );
			// Shop domain.
			$this->addElement( 'hidden', 'shop_domain' );
			$this->shop_domain->removeDecorator( 'label' );
			$this->addElement( 'text', 'shop_domain_displayed', array (
				'label' => 'shop domain',
				'disabled' => 'disabled'
			) );
			// Api user. TODO move text to translator.
			$config = Config::getInstance();
			$this->addElement( 'text', 'api_user', array (
				'label' => 'API User <span>( <a href="'. $config->plugin->center->baseUrl .'default/index/page/bigcommerce/key" target="_blank">Click here</a> to find how to get it )</span>',
			) );
			$this->getElement( 'api_user' )->getDecorator( 'Label' )->setOption( 'escape', false );
			// Api token.
			$this->addElement( 'text', 'api_key', array (
				'label' => 'API Token <span>( <a href="'. $config->plugin->center->baseUrl .'default/index/page/bigcommerce/key" target="_blank">Click here</a> to find how to get it )</span>'
			) );
			$this->getElement( 'api_key' )
				->getDecorator( 'Label' )
				->setOption( 'escape', false )
				;
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
					array ( 'Label', array (
						'placement' => 'append',
						'escape' => false
					) ),
					array ( 'HtmlTag', array (
						'tag' => 'dt',
						'class' => 'news-subscription'
					) )
				)
			) );
			$this->addElement( 'checkbox', 'product_news', array (
				'label' => 'send me product news',
				'decorators' => array (
					'ViewHelper',
					array ( 'Label', array (
						'placement' => 'append',
						'escape' => false
					) ),
					array ( 'HtmlTag', array (
						'tag' => 'dt',
						'class' => 'news-subscription'
					) )
				)
			) );
			// Submit button.
			$this->addElement( 'submit', 'save', array (
				'label' => 'save'
			) );
			$this->getElement( 'save' )
				->removeDecorator( 'DtDdWrapper' );
			$this->getElement( 'save' )
				->addDecorator( 'HtmlTag', array (
					'tag' => 'dt',
					'class' => 'buttons',
					'placement' => 'prepend',
					'openOnly' => true
				) );
			// Cancel button.
			$url = Zend_Controller_Front::getInstance()
				->getRouter()->assemble( array (
					'module' => 'bigcommerce',
					'controller' => 'auth',
					'action' => 'account',
					'plugin' => Zend_Controller_Front::getInstance()
						->getRequest()->getParam( 'plugin' )
				), false, 'default' );
			$this->addElement( 'button', 'cancel', array (
				'label' => 'cancel',
				'onclick' => "location.href = '{$url}';"
			) );
			$this->getElement( 'cancel' )
				->removeDecorator( 'DtDdWrapper' );
		}
	}
