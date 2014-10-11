<?php
	/**
	 * Mailchimp filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.3
	 */
	class Admin_Form_User_MailchimpFilter extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Filter' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction( $this->getView()->url() )
				->setOptions( array (
					'id' => 'filter-form'
				) );
			// Platform.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all platforms' )
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multioptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform', array (
				'label' => 'platform',
				'required' => true,
				'multioptions' => $multioptions,
				'validators' => array (
					'Int'
				)
			) );
			$this->getElement( 'platform' )
				->getDecorator( 'Filter' )
				->setOption( 'sm','4' )
				;
			// News type.
			$this->addElement( 'select', 'news_type', array (
				'label' => 'news type',
				'required' => true,
				'multioptions' => array (
					'all customers news' => $this->getTranslator()->_( 'news for all customers' ),
					'product news' => $this->getTranslator()->_( 'promo news' ),
					'update news' => $this->getTranslator()->_( 'updates for app' ),
					'critical updates' => $this->getTranslator()->_( 'critical updates for app' ),
					'uninstalled apps' => $this->getTranslator()->_( 'uninstalled apps' ),
				),
				'validators' => array (
					array (
						'validator' => 'regex', 'options' => array (
							'pattern' => '/[\w\s]+/'
						)
					)
				)
			) );
			$this->getElement( 'news_type' )
				->getDecorator( 'Filter' )
				->setOption( 'sm','4' )
				;
			// Plugin.
			$multioptions = array ();
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multioptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin', array (
				'label' => 'plugin',
				'disabled' => 'disabled',
				'multioptions' => $multioptions,
				'validators' => array (
					'Int'
				)
			) );
			$this->getElement( 'plugin' )
				->getDecorator( 'Filter' )
				->setOption( 'sm','4' )
				;
		}
	}
