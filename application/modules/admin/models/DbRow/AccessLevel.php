<?php
/**
 * DbRow of access_levels DbTable.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbRow_AccessLevel extends Zend_Db_Table_Row
{
	/**
	 * check level for unlimited access
	 * @return bool
	 */
	public function isSuperAdmin()
	{
		if($this->name == 'superadmin'){
			return true;
		}else{
			return false;
		}
	}
}