<?php
	/**
	 * Cron edit form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_Form_Cron_Edit extends Admin_Form_Cron_Create
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
		}
	}
