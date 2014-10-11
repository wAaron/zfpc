<?php
/**
 * AccessResources db table.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbTable_AccessResources extends D_Db_Table_Abstract
{
	protected $_name = 'access_resources';

	protected $_rowClass = 'Admin_Model_DbRow_AccessResource';

	/**
	 * get all resources
	 * @param bool $visibleOnly
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getAll($visibleOnly = false)
	{
		return $this->fetchAll(
			$this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'r' => $this->info( 'name' ) ),
					array ( '*' )
				)
				->where('visible = ?', (int) $visibleOnly)
		);
	}


	/**
	 * get resources with access to them by level id
	 * @param $levelId
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getByLevelWithPermissions( $levelId, $visibleOnly = false )
	{
		$select = $this->select()
			->setIntegrityCheck( false )
			->from(
				array ( 'r' => $this->info( 'name' ) ),
				array ( '*' )
			)
			->joinLeft(
				array( 'p' => Table::_('permissions')->info('name')),
				"p.resource_id = r.id and p.level_id = $levelId",
				'p.allowed'
			);

		if($visibleOnly){
			$select->where('visible = 1');
		}

		return $this->fetchAll( $select	);
	}


	/**
	 * get list of resources
	 * @return array
	 */
	public function getInheritedListForLevel($levelId)
	{
		//get all visible resources
		$resourcesList = $this->getByLevelWithPermissions($levelId, true)
			->toArray();

		return $this->prepareInheritedArray($resourcesList);
	}


	/**
	 * prepare multi array to special format (for recursive output)
	 * @param $resources
	 * @return array
	 */
	protected function prepareInheritedArray($resources){

		$reformattedList = array();
		//key of array element is id of the parent element
		foreach ($resources as $resource){
			if(empty($reformattedList[$resource['parent_id']])) {
				$reformattedList[$resource['parent_id']] = array();
			}
			$reformattedList[$resource['parent_id']][] = $resource;
		}

		return $reformattedList;
	}
}
