<?php
	/**
	 * Tariff plan edit form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_TariffPlan_Edit extends Admin_Form_TariffPlan_Create
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
			$this->getElement( 'platform_id' )
				->setAttrib( 'disabled', 'disabled' );
			$this->getElement( 'plugin_id' )
				->setAttrib( 'disabled', 'disabled' );
			$this->getElement( 'payment_plan_id' )
				->setAttrib( 'disabled', 'disabled' );
		}
	}
