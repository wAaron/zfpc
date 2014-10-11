<?php
	/**
	 * Email template edit form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_EmailTemplate_Edit extends Admin_Form_EmailTemplate_Create
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
					'id' => 'template-edit-form'
				) );
			// Elements.
			$this->getElement( 'platform_id' )
				->setOptions( array (
					'disabled' => 'disabled',
					'validators' => array ()
				) );
			$this->getElement( 'type' )
				->setOptions( array (
					'required' => false,
					'disabled' => 'disabled',
					'validators' => array ()
				) );
			$this->getElement( 'plugin_id' )
				->setOptions( array (
					'validators' => array ()
				) );
		}
	}
