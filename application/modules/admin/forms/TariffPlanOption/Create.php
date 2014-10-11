<?php
	/**
	 * Tariff plan option create form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_TariffPlanOption_Create extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Default' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction(
					$this->getView()->url( array (
						'action' => 'create'
					) )
				)
				->setOptions( array (
					'id' => 'create-form',
					'class' => 'form-horizontal'
				) );
			// Name.
			$this->addElement( 'text', 'name', array (
				'label' => 'name',
				'required' => true,
				'validators' => array (
					array (
						'Alnum', true, array (
							'allowWhiteSpace' => true
						)
					)
				)
			) );
			// Key.
			$multiOptions = Table::_( 'options' )->getEnumOptions( 'key' );
			$multiOptions = array_combine( $multiOptions, $multiOptions );
			$this->addElement( 'select', 'key', array (
				'label' => 'key',
				'required' => true,
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Alnum'
				)
			) );
			// Value.
			$this->addElement( 'text', 'value', array (
				'label' => 'value',
				'required' => true
			) );
			// Type.
			$multiOptions = Table::_( 'options' )->getEnumOptions( 'type' );
			$multiOptions = array_combine( $multiOptions, $multiOptions );
			$this->addElement( 'select', 'type', array (
				'label' => 'type',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Alpha'
				)
			) );
			// Unit.
			$this->addElement( 'text', 'unit', array (
				'label' => 'unit',
				'validators' => array (
					'Alpha'
				)
			) );
			// Unit after.
			$this->addElement( 'checkbox', 'unit_after', array (
				'label' => 'unit after',
				'class' => 'form-checkbox',
				'validators' => array (
					'Int'
				)
			) );
			// Display.
			$this->addElement( 'checkbox', 'display', array (
				'label' => 'display',
				'class' => 'form-checkbox',
				'validators' => array (
					'Int'
				)
			) );
			// Use for payment.
			$this->addElement( 'checkbox', 'use_for_payment', array (
				'label' => 'use for payment',
				'class' => 'form-checkbox',
				'validators' => array (
					'Int'
				)
			) );
			// Use for payment.
			$this->addElement( 'text', 'price_for_overdraft_unit', array (
				'label' => 'price for overdraft unit',
				'validators' => array (
					'Float'
				)
			) );
			// Use for payment.
			$this->addElement( 'text', 'overdraft_unit_count', array (
				'label' => 'overdraft unit count',
				'validators' => array (
					'Int'
				)
			) );
		}
	}
