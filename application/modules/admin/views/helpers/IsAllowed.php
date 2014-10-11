<?php

/**
 * Helper for checking access for resource.
 *
 * @author Kuksanau Ihnat
 * @copyright 2014 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_View_Helper_IsAllowed extends Zend_View_Helper_Abstract
{
	/**
	 * model of admin.
	 * @var string
	 */
	protected $_viewer = null;

	/**
	 * check is current resource is allowed for viewer
	 * @param $resourceName
	 * @return bool
	 */
	public function isAllowed( $resourceName, $privilegeName = null )
	{
		$isAllowed = false;

		$viewer = $this->getViewer();
		if( $viewer ){
			$acl = Zend_Registry::get( 'acl' );
			//check is resource will be allowed
			$isAllowed = $acl->isAllowed( $viewer->access_level, $resourceName, $privilegeName );
		}

		return $isAllowed;
	}


	/**
	 * get current viewer
	 * @return mixed|string
	 */
	protected function getViewer()
	{
		//get item of viewer
		if( !$this->_viewer ){
			$this->_viewer =  Table::_( 'admins' )->getViewer();
		}

		return $this->_viewer;
	}
}
