<?php
/**
 * AccessLevels db table.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbTable_AccessLevels extends D_Db_Table_Abstract
{
	protected $_name = 'access_levels';

	protected $_rowClass = 'Admin_Model_DbRow_AccessLevel';

	/**
	 * get list of all levels
	 * @return array
	 */
	public function getLevels()
	{
		$levels = $this->fetchAll(
			$this->select()
				->from(
					$this->info('name'),
					array('*')
				)
		);

		return $levels;
	}
}
