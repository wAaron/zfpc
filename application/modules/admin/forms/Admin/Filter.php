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
	class Admin_Form_Admin_Filter extends D_Admin_Form_Filter
	{
		public function init()
		{
			//clear element list
			$this->setDefaultElementsList();
			parent::init();

			// level.
			$levels = Table::_('levels')->getLevels();
			//prepare list to select
			$multiOptions = array();
			$multiOptions[$this->getTranslator()->_( '' )] = $this->getTranslator()->_( 'all levels' );

			foreach($levels as $level){
				$multiOptions[$level['id']] =  $this->getTranslator()->_($level['name']);
			}

			$this->addElement( 'select', 'level', array (
				'label' => 'Access level',
				'required' => true,
				'multiOptions' => $multiOptions,
			) );

		}
	}
