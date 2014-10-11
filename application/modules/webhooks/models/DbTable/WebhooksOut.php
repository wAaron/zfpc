<?php
/**
 * Outcoming Webhooks db table.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Webhooks
 * @version 1.0.0
 */
class Webhooks_Model_DbTable_WebhooksOut extends D_Db_Table_StatusItems_Abstract
{
    protected $_name = 'webhooks_out';

    protected $_rowClass = 'Webhooks_Model_DbRow_WebhookOut';

    /**
     * date field name
     * @var string
     */
    protected $_dateFieldName = 'income_time';

    /**
     * get pending webhooks
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getAllPending()
    {
        return $this->fetchAll(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'status = ?', 'wait' )
        );
    }


    /**
     * get full list for view
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getAll()
    {
        return $this->fetchAll(
            $this->select()
                ->setIntegrityCheck( false )
                ->from(
                    array ( 'w' => $this->info( 'name' ) ),
                    array ( '*' )
                )
                ->joinLeft(
                    array ( 'pn' => Table::_( 'plugins' )->info( 'name' ) ),
                    'w.plugin_id = pn.id',
                    array ( 'plugin' => 'pn.name' )
                )
                ->joinLeft(
                    array ( 'pm' => Table::_( 'platforms' )->info( 'name' ) ),
                    'w.platform_id = pm.id',
                    array ( 'platform' => 'pm.name' )
                )
        );
    }

	/**
	 * get items by params
	 * @param $params
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getByParams($params)
	{
		$select = $this->select()
			->setIntegrityCheck( false )
			->from(
				array ( 'w' => $this->info( 'name' ) ),
				array ( '*' )
			)
			->joinLeft(
				array ( 'pn' => Table::_( 'plugins' )->info( 'name' ) ),
				'w.plugin_id = pn.id',
				array ( 'plugin' => 'pn.name' )
			)
			->joinLeft(
				array ( 'pm' => Table::_( 'platforms' )->info( 'name' ) ),
				'w.platform_id = pm.id',
				array ( 'platform' => 'pm.name' )
			)
			->order('w.id DESC');

		// Filter by platform.
		if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
			$select->where( 'w.platform_id = ?', $params['platform'] );
		}
		// Filter by plugin.
		if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
			$select->where( 'w.plugin_id = ?', $params['plugin'] );
		}
		// Filter by status.
		if ( isset ( $params['status'] ) && !empty ( $params['status'] ) ) {
			$select->where( 'w.status = ?', $params['status'] );
		}

		return $this->fetchAll( $select );
	}
}
