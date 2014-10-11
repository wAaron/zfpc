<?php
/**
 * User create form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Levels_Create extends Zend_Form
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

		// Level Name.
		$this->addElement( 'text', 'name', array (
			'label' => 'Name',
			'description' => 'System level name',
			'required' => true,
			'validators' => array (
				array (
					'Alnum', true, array (
						'allowWhiteSpace' => false
					),
					array('StringLength', false, array(0, 50)),
				),
			)
		) );
		$this->getElement('name')->addValidator(
			'Db_NoRecordExists', false,	array(
				'table'     => 'access_levels',
				'field'     => 'name',
			)
		);

		// Title
		$this->addElement( 'text', 'title', array (
			'label' => 'Title',
			'description' => 'Public level name',
			'required' => true,
			'validators' => array (
				array (
					'Alnum', true, array (
						'allowWhiteSpace' => true
					)
				),
			)
		) );
	}
}
