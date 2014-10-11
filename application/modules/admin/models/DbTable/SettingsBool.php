<?php
	/**
	 * Boolean values of settings db table .
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Model_DbTable_SettingsBool extends D_Db_Table_Abstract
	{
		protected $_name = 'settings_bool';

		protected $_primary = 'setting_id';
	}
