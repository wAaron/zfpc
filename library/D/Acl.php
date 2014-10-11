<?php
/**
 * Custom ACL extends Zend_Acl
 *
 * @author Kuksanau Ihnat
 * @copyright 2014 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package D
 * @version 1.0.0
 */
class D_Acl extends Zend_Acl
{
	/**
	 * empty privilege specifier
	 */
	const EMPTY_PRIVILEGE = '*';

	/**
	 * adds resource
	 * @param string|Zend_Acl_Resource_Interface $resource
	 * @param null $parent
	 * @return $this|Zend_Acl
	 */
	public function addResource( $resource, $parent = null )
	{
		//check for duplicates
		if( !$this->has( $resource ) ){
			parent::addResource( $resource, $parent );
		}

		return $this;
	}

	/**
	 * allows resource
	 * @param null $roles
	 * @param null $resources
	 * @param null $privileges
	 * @param Zend_Acl_Assert_Interface $assert
	 * @return Zend_Acl
	 */
	public function allow( $roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null )
	{
		//allow access to default privilege if privilege has been not specified
		if( !$privileges ){
			$privileges = self::EMPTY_PRIVILEGE;
		}
		return parent::allow( $roles, $resources, $privileges, $assert );
	}

	/**
	 * allows resource
	 * @param null $roles
	 * @param null $resources
	 * @param null $privileges
	 * @param Zend_Acl_Assert_Interface $assert
	 * @return Zend_Acl
	 */
	public function deny( $roles = null, $resources = null, $privileges = null, Zend_Acl_Assert_Interface $assert = null )
	{
		//deny access to default privilege if resource has been forbidden
		if( !$privileges ){
			$privileges = self::EMPTY_PRIVILEGE;
		}

		return parent::deny( $roles, $resources, $privileges, $assert );
	}

	/**
	 * checks is current resource allowed
	 * @param null $role
	 * @param null $resource
	 * @param null $privilege
	 * @return bool
	 */
	public function isAllowed( $role = null, $resource = null, $privilege = null )
	{
		//check is allowed access to default privilege if privilege has been not specified
		if( !$privilege ){
			$privilege = self::EMPTY_PRIVILEGE;
		}

		//if resource has been not specified allow it
		if( $this->has( $resource ) ){
			$isAllowed = parent::isAllowed( $role, $resource, $privilege );
		} else{
			$isAllowed = true;
		}

		return $isAllowed;
	}
}