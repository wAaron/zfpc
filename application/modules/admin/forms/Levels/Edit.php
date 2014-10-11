<?php
/**
 * User edit form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Levels_Edit extends Admin_Form_Levels_Create
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

		$this->getElement('name')->removeValidator('Db_NoRecordExists');
	}
}
