<?php
/**
 * Filter form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_WebhooksOut_Filter extends D_Admin_Form_Filter
{
	public function init()
	{
		parent::init();
		$this->setAction( $this->getView()->url(
			array (
				'module' => 'admin',
				'controller' => 'webhooks-out',
				'action' => 'index'
			), null, true
		) );

		//Status
        $multiOptions = array('' => 'All statuses') +  Table::_('webhooksOut')->getEnumOptions('status');
		$this->addElement( 'select', 'status', array (
			'label' => 'Status',
			'multioptions' => $multiOptions,
			'value' => ''
		) );
	}
}
