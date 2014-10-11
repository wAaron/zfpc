<?php
/**
 * AccessPermissions db table.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbTable_AccessPermissions extends D_Db_Table_Abstract
{
	protected $_name = 'access_permissions';

	public function getForLevel( $levelId )
	{
		return $this->fetchAll(
			$this->select()
				->setIntegrityCheck( false )
				->from(
					array( $this->info( 'name' ) ),
					array( 'resource_id' )
				)
				->where( 'level_id = ?', (int)$levelId )
		);
	}

	/**
	 * update permissions for level
	 * @param $levelId
	 * @param array $resourcesIds
	 * @return Zend_Db_Statement_Interface
	 */
	public function saveForLevel( $levelId, $resourcesIds = array() )
	{
		//clear old permissions
		$this->clearForLevel( $levelId );

		//multiple save
		if( $resourcesIds ){
			$db = $this->getAdapter();
			$query = 'INSERT INTO ' . $db->quoteIdentifier( $this->info( 'name' ) ) . ' (`resource_id`, `level_id`, `allowed`) VALUES ';
			$queryVals = array();
			foreach( $resourcesIds as $id ) {
				$id = $db->quote( $id );
				$queryVals[] = '(' . $id . ',' . $levelId . ', 1)';
			}
			$db->query( $query . implode( ',', $queryVals ) );
		}

		return true;
	}


	/**
	 * delete old permissions for this level
	 * @param $levelId
	 * @return int
	 */
	public function clearForLevel( $levelId )
	{
		return $this->delete( array(
			"level_id = {$levelId}"
		) );
	}
}
