<?php
/**
 * Admin create form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Admin_Create extends Zend_Form
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

		// Full Name.
		$this->addElement( 'text', 'fullname', array (
			'label' => 'Full Name',
			'description' => 'first and last name',
			'required' => false,
			'validators' => array (
				array (
					'Alnum', true, array (
						'allowWhiteSpace' => true
					),
					array('StringLength', false, array(0, 255)),
				),
			)
		) );

		// Email.
		$this->addElement( 'text', 'email', array (
			'label' => 'Email',
			'description' => 'email of the new admin',
			'required' => true,
			'validators' => array (
				new Zend_Validate_EmailAddress()
			)
		) );

		// login
		$this->addElement( 'text', 'nickname', array (
			'label' => 'Login',
			'description' => 'unique nickname',
			'required' => true,
			'validators' => array (
				array (
					'Alnum', true, array (
						'allowWhiteSpace' => false
					)
				),
			)
		) );
		$this->getElement('nickname')->addValidator(
			'Db_NoRecordExists', false,	array(
				'table'     => 'admins',
				'field'     => 'nickname',
			)
		);

		$passwordConfirmation = new D_Validate_PasswordConfirmation();
		// Password.
		$this->addElement( 'password', 'password', array (
			'label' => 'Password',
			'description' => 'from 6 to 30 symbols',
			'required' => true,
			'validators' => array(
				$passwordConfirmation,
				array('Alnum'),
				array('StringLength', false, array(6, 30)),
			),

		) );
		//Confirm Password
		$this->addElement('password', 'password_confirm', array(
			'label' => 'Confirm Password',
			'description' => 'password again',
			'required' => true,
			'validators' => array(
				$passwordConfirmation,
				array('Alnum'),
				array('StringLength', false, array(6, 100)),
			),
		));

		// level.
		$levels = Table::_('levels')->getLevels();
		//prepare list to select
		$multiOptions = array();
		foreach($levels as $level){
			$multiOptions[$level['id']] =  $this->getTranslator()->_($level['name']);
		}

		$this->addElement( 'select', 'access_level', array (
			'label' => 'Access level',
			'required' => true,
			'multiOptions' => $multiOptions,
		) );
		$this->setDefault('access_level',1);

	}
}
