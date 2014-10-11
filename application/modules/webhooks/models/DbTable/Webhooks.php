<?php
/**
 * Webhooks db table.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Webhooks
 * @version 1.0.0
 */
class Webhooks_Model_DbTable_Webhooks extends D_Db_Table_Abstract
{
    protected $_name = 'webhooks';

    protected $_rowClass = 'Webhooks_Model_DbRow_Webhook';


	/**
	 * save webhook to local db, and check necessity to register via api
	 * @param $data
	 */
	public function addItem($data)
	{
		//add row
		$webhook = $this->createRow( array(
			'platform_id' => $data['platform_id'],
			'plugin_id' => $data['plugin_id'],
			'user_id' => $data['user_id'],
			'topic' => $data['topic'],
			'callback_url' => $data['callback_url'],
			'domain' => $data['domain'],
			'create_time' => date( LOCAL_DATETIME_FORMAT )
		) );
		$webhook->save();

		//check if it has a similar webhook just registered via api
		$webhook->checkParent();

		return $webhook;
	}


    /**
     * get webhook by requested params
     * @param $params
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function getWebhookByParams( $params, $isNotId = null )
    {
        $select = $this->select()
            ->setIntegrityCheck( false )
            ->from(
                array( 'w' => $this->info( 'name' ) ),
                array( '*' )
            )
            ->limit( 1 );

        //add filter by params
        foreach( $params as $field => $value ) {
            if( in_array( $field, $this->_cols ) ){
                $select->where( "w.{$field} = ?", $value );
            }
        }

		if($isNotId){
			$select->where( "w.id != ?", $isNotId );
		}

        return $this->fetchRow(
            $select
        );
    }

    /**
     * get webhooks which have to be installed
     * @param $pluginID
     * @param $userID
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getWebhooksToInstall( $pluginID, $userID )
    {
        return $this->fetchAll(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'webhook_id = ?', 0 )
                ->where( 'registered = ?', 1 )
                ->where( 'plugin_id = ?', $pluginID )
                ->where( 'user_id = ?', $userID )
        );
    }


    /**
     * get webhooks which have been registered via api
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getAllRegisteredWebhooks()
    {
        return $this->fetchAll(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'registered = ?', 1 )
                ->where( 'webhook_id != ?', 0 )
        );
    }

    /**
     * get childs by parent
     * @param $parentID
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getByParentId( $parentID )
    {
        return $this->fetchRow(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'parent_id = ?', $parentID )
                ->limit( 1 )
        );
    }


    /**
     * get list of topics
     * @return array
     */
    public function getTopicValues()
    {
        $rows = $this->fetchAll(
            $this->select()
                ->distinct()
                ->from( $this->info( 'name' ), array( 'topic' ) )
        );
        $topics = array();
        foreach( $rows as $row ) {
            $topics[$row->topic] = $row->topic;
        }

        return $topics;
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
                    array( 'w' => $this->info( 'name' ) ),
                    array( '*' )
                )
                ->joinLeft(
                    array( 'pn' => Table::_( 'plugins' )->info( 'name' ) ),
                    'w.plugin_id = pn.id',
                    array( 'plugin' => 'pn.name' )
                )
                ->joinLeft(
                    array( 'pm' => Table::_( 'platforms' )->info( 'name' ) ),
                    'w.platform_id = pm.id',
                    array( 'platform' => 'pm.name' )
                )
        );
    }


    /**
     * get unregistered webhooks by plugin id and user id
     * @param $domain
     * @param $pluginID
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function getByPlugin( $pluginID, $userID )
    {
        return $this->fetchAll(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'user_id = ?', $userID )
                ->where( 'plugin_id = ?', $pluginID )
        );
    }

	/**
	 * get webhooks by params
	 * @param $params
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getByParams($params)
	{
		$select = $this->select()
			->setIntegrityCheck( false )
			->from(
				array( 'w' => $this->info( 'name' ) ),
				array( '*' )
			)
			->joinLeft(
				array( 'pn' => Table::_( 'plugins' )->info( 'name' ) ),
				'w.plugin_id = pn.id',
				array( 'plugin' => 'pn.name' )
			)
			->joinLeft(
				array( 'pm' => Table::_( 'platforms' )->info( 'name' ) ),
				'w.platform_id = pm.id',
				array( 'platform' => 'pm.name' )
			)
			->order('w.id DESC')
		;

		// Filter by platform.
		if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
			$select->where( 'w.platform_id = ?', $params['platform'] );
		}
		// Filter by plugin.
		if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
			$select->where( 'w.plugin_id = ?', $params['plugin'] );
		}
		// Filter by topic.
		if ( isset ( $params['topic'] ) && !empty ( $params['topic'] ) ) {
			$select->where( 'w.topic = ?', $params['topic'] );
		}
		// Filter by state.
		if ( isset ( $params['registered'] ) && !empty ( $params['registered'] ) ) {
			$registered = $params['registered'] == 'Yes' ? 1 : 0;
			$select->where( 'w.registered = ?', $registered );
		}

		return $this->fetchAll($select);
	}

}
