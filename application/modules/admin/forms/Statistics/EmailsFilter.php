<?php
/**
 * Emails filter form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2014 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Statistics_EmailsFilter extends D_Admin_Form_StatisticsFilter
{
	public function init()
	{
		parent::init();
		$this->setOptions( array(
			'id' => 'emails-filter-form'
		) );

		//add System option
		$platform = $this->getElement( 'platform' );
		$platform->options[0] = $this->getTranslator()->_( 'system' );
	}
}