<?php
	/**
	 * Install filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 2.0.0
	 */
	class Admin_Form_Statistics_InstallFilter extends D_Admin_Form_StatisticsFilter
	{
		public function init()
		{
			parent::init();
			$this->setOptions( array (
				'id' => 'install-filter-form'
			) );
		}
	}
