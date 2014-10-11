<?php
	/**
	 * email edit form.
	 *
	 * @author Kuksanau Ihnat
	 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_Emails_Edit extends Zend_Form
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
						'action' => 'edit'
					) )
				)
				->setOptions( array (
					'id' => 'edit-form',
					'class' => 'form-horizontal'
				) );

			// To.
			$this->addElement( 'text', 'to', array (
				'label' => 'To',
				'required' => true,
				'validators' => array (
					new Zend_Validate_EmailAddress()
				)
			) );

			// From.
			$this->addElement( 'text', 'from', array (
				'label' => 'From',
				'required' => true,
				'validators' => array (
					new Zend_Validate_EmailAddress()
				)
			) );

			// Subject
			$this->addElement( 'text', 'subject', array (
				'label' => 'Subject',
				'required' => true,
				'validators' => array (
					array (
						'Alnum', true, array (
						'allowWhiteSpace' => true
					)
					),
				)
			) );

			// Priority
			$this->addElement( 'text', 'priority', array (
				'label' => 'Priority',
				'required' => true,
				'validators' => array (
					'Int',
					new Zend_Validate_Between(array('min' => 0, 'max' => 99))
				)
			) );

			// Status
			$this->addElement( 'select', 'status', array (
				'label' => 'Status',
				'required' => true,
				'multiOptions' => array(
					'wait' => 'wait',
					'sent' => 'sent',
					'error' => 'error',
					'sent by app' => 'sent by app'
				),
			) );
		}
	}
