<?php
/**
 * Admin edit form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Admin_Edit extends Admin_Form_Admin_Create
{
	public function init()
	{
		parent::init();
		$this->setAction(
			$this->getView()->url( array (
				'action' => 'edit'
			) )
		)
			->setOptions( array (
				'id' => 'edit-form'
			) );

		$this->getElement('password')
			->setRequired(false)
			->setValue('');

		$this->getElement('password_confirm')
			->setRequired(false);

		$this->getElement('nickname')->setValidators(array(
			array (
				'Alnum', true, array ( 'allowWhiteSpace' => false )
			),
		));

		//allow to edit access_level only to superadmin
		$viewer = Table::_('admins')->getViewer();
		if(!$viewer->isSuperAdmin()){
			$this->getElement( 'access_level' )
				->setOptions( array (
					'required' => false,
					'disabled' => 'disabled',
					'validators' => array ()
				) );
		}
	}
}
