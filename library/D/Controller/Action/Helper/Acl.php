<?php

/**
 * Acl helper.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2014 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package D
 * @version 1.0.0
 */
class D_Controller_Action_Helper_Acl extends Zend_Controller_Action_Helper_Abstract
{
	/**
	 * build acl object by user role
	 * @param $levelId
	 * @return Zend_Acl
	 */
	public function buildAcl( $levelId )
	{
		$acl = new D_Acl();
		//get role model
		$level = Table::_( 'levels' )->get( $levelId );
		//if viewer has not access level - deny all resources
		if(!$level){
			$acl->deny();
			return $acl;
		}

		$acl->allow();
		$acl->addRole( new Zend_Acl_Role( $level->id ) );

		//superadmins have unlimited access
		if($level->isSuperAdmin()){
			return $acl;
		}

		//get permissions to resources for current level
		$permissions = Table::_( 'resources' )
			->getByLevelWithPermissions( $level->id )
			->toArray();

		foreach( $permissions as $accessToResource ) {
			//init resources
			$acl->addResource( new Zend_Acl_Resource( $accessToResource['resource_name'] ) );
			//allow access or deny if resource is forbidden
			if( $accessToResource['allowed'] ){
				$acl->allow( $level->id, $accessToResource['resource_name'], $accessToResource['privilege_name'] );
			} else{
				$acl->deny( $level->id, $accessToResource['resource_name'], $accessToResource['privilege_name'] );
			}
		}

		return $acl;
	}


}
