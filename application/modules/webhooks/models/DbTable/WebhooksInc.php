<?php
/**
 * Incoming Webhooks db table.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Webhooks
 * @version 1.1.0
 */
class Webhooks_Model_DbTable_WebhooksInc extends D_Db_Table_Abstract
{
	protected $_name = 'webhooks_inc';

	protected $_rowClass = 'Webhooks_Model_DbRow_WebhookInc';

	/**
	 * get pending webhooks
	 * @return Zend_Db_Table_Rowset_Abstract
	 * @author Kuksanau Ihnat
	 */
	public function getAllPending()
	{
		return $this->fetchAll(
			$this->select()
				->from( $this->info('name') )
				->where ( 'status = ?','wait' )
		);
	}

	/**
	 *
	 * @param Webhooks_Model_DbTable_Webhooks $webhooksTable
	 * @param Default_Model_DbTable_Users $usersTable
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getForOut(Webhooks_Model_DbTable_Webhooks $webhooksTable)
	{
		return $this->fetchAll(
			$this->select()
				->setIntegrityCheck(false)
				->from( array( 'i' => $this->info('name') ) )
				->joinInner(
					array('w' => $webhooksTable->info('name')),
					'i.domain = w.domain and i.topic = w.topic',
					array(
						'registered_id' => 'w.id',
						'callback_url' => 'w.callback_url',
						'platform_id' => 'w.platform_id',
                        'registered_plugin_id' => 'w.plugin_id'
					)
				)
				->where( 'i.status = ?', 'verified' )
		);
	}

	/**
	 * delete items which have been processed
	 * @param $ids
	 * @return int
	 */
	public function clearProcessed( $ids )
	{
		$where = $this->getAdapter()->quoteInto('id IN (?)', $ids);

		return $this->delete($where);
	}


}
