<?php
	/**
	 * Instance filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_Statistics_InstanceFilter extends Zend_Form
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
					'id' => 'instance-filter-form'
				) );
			// Shop.
			$this->addElement( 'text', 'shop', array (
				'label' => 'shop',
				'class' => 'form-control',
			) );
			// Email.
			$this->addElement( 'text', 'email', array (
				'label' => 'email',
				'class' => 'form-control',
			) );
			// Buttons.
			$this->addElement( 'button', 'filter', array (
				'label' => '&nbsp;',
				'value' => $this->getTranslator()->_( 'apply filter' ),
				'class' => 'btn btn-primary form-control'
			) );
			$this->getElement( 'filter' )
				->getDecorator( 'filter' )
				->setOption( 'escape', false )
				;
		}
	}
