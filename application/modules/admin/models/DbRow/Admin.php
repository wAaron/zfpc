<?php
/**
 * DbRow of admins DbTable.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbRow_Admin extends Zend_Db_Table_Row
{
	const SUPERADMIN_ROLE_NAME = 'superadmin';

	/**
	 * check if user is superadmin
	 * @return bool
	 */
	public function isSuperAdmin()
	{
		$role = Table::_('levels')->get($this->access_level);
		if( $role->name == self::SUPERADMIN_ROLE_NAME ){
			return true;
		} else{
			return false;
		}
	}


	/**
	 * check is this item has access to current resource
	 * @param $resourceName
	 * @return bool
	 */
	public function isAllowed( $resourceName, $privilegeName = null )
	{
		$acl = Zend_Registry::get( 'acl' );

		return  $acl->isAllowed( $this->access_level, $resourceName, $privilegeName );
	}


}
