<?php
	/**
	 * Filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_User_Filter extends Zend_Form
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
				->setAction( $this->getView()->url(
					array (
						'module' => 'admin',
						'controller' => 'users',
						'action' => 'index'
					), null, true
				) )
				->setOptions( array (
					'id' => 'filter-form'
				) );
			// Page.
			$this->addElement( 'hidden', 'page' );
			$this->getElement( 'page' )
				->removeDecorator( 'filter' )
				->addDecorator( 'ViewHelper' )
				;
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
				'multioptions' => $multioptions
			) );
			// User.
			$this->addElement( 'text', 'user', array (
				'label' => 'username',
			) );
			// Shop.
			$this->addElement( 'text', 'shop', array (
				'label' => 'shop'
			) );
			// Email.
			$this->addElement( 'text', 'email', array (
				'label' => 'email'
			) );
			// Buttons.
			$this->addElement( 'button', 'filter', array (
				'label' => '&nbsp;',
				'content' => $this->getTranslator()->_( 'apply filter' ),
				'class' => 'btn btn-primary form-control'
			) );
			$this->getElement( 'filter' )
				->getDecorator( 'filter' )
				->setOption( 'escape', false )
				;
		}
	}
