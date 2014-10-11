<?php
/**
 * Plugin Center permissions.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.2
 */
class Admin_LevelsController extends D_Admin_Controller_Abstract
{
	public function init()
	{
		parent::init();
		$this->_createForm = new Admin_Form_Levels_Create();
		$this->_editForm = new Admin_Form_Levels_Edit();
		$this->_entity = 'level';
		$this->_table = 'levels';
	}


	/**
	 * manage levels
	 */
	public function indexAction()
	{
		//set title
		$translate = Zend_Registry::get( 'translate' );
		$this->view->title = $translate->_( 'access levels' );
		$this->view->headTitle( $this->view->title );

		//get admins list
		$this->view->levels = $levels = Table::_($this->_table)->getLevels();
	}

	/**
	 * manage permissions for current level
	 */
	public function permissionsAction()
	{
		//post
		if( $this->_request->isPost() ){
			$values = $this->_request->getPost();
			//get resources ids
			$resourcesIds = array();
			foreach( $values as $key => $value ) {
				if( $value == 'on' ){
					$resourcesIds[] = $key;
				}
			}
			//save data
			Table::_( 'permissions' )->saveForLevel( $values['level_id'], $resourcesIds );
			$this->view->message = Zend_Registry::get( 'translate' )->_(
				'level permissions has been successfully updated'
			);
			//get
		}else{
			$level = Table::_( 'levels' )->get( $this->_getParam( 'id', 0 ) );
			//get resources for current level
			$resources = Table::_( 'resources' )->getInheritedListForLevel( $level->id );

			$this->view->level = $level;
			$this->view->resources = $resources;
		}

		Zend_Layout::getMvcInstance()->disableLayout();
	}


	/**
	 * access denied page
	 */
	public function accessDeniedAction()
	{
		//set title
		$translate = Zend_Registry::get( 'translate' );
		$this->view->title = $translate->_( 'access denied' );
		$this->view->headTitle( $this->view->title );
	}

}
