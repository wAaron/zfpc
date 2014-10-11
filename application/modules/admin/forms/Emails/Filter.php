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
class Admin_Form_Emails_Filter extends D_Admin_Form_Filter
{
	public function init()
	{
		parent::init();
		$this->setAction( $this->getView()->url(
				array (
					'module' => 'admin',
					'controller' => 'emails',
					'action' => 'index'
				), null, true
			) );

		//status
		$this->addElement( 'select', 'status', array (
			'label' => 'Status',
			'multioptions' => array(
				'' => 'All statuses',
				'wait' => 'wait',
				'sent' => 'sent',
				'error' => 'error',
				'sent by app' => 'sent by app'
			),
			'value' => ''
		) );
	}
}
