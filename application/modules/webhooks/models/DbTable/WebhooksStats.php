<?php
/**
 * Stats Webhooks db table.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Webhooks
 * @version 1.0.0
 */
class Webhooks_Model_DbTable_WebhooksStats extends D_Db_Table_Abstract
{
    protected $_name = 'webhooks_stats';

    protected $_rowClass = 'Webhooks_Model_DbRow_WebhookStats';


    /**
     * increment webhook sent stats
     * @param $platformID
     * @param $pluginID
     * @param $date
     * @return mixed|null|Zend_Db_Table_Row_Abstract
     */
    public function increment( $platformID, $pluginID, $date = null )
    {
        if(!$date){
            $date = date( LOCAL_DATE_FORMAT );
        }
        $row = $this->getByDate( $platformID, $pluginID, $date );

        //increment column qty if founded
        if($row){
            $row->qty++;
            $row->save();
        }else{//or create a new one
            $row = $this->createRow(array(
                'platform_id' => $platformID,
                'plugin_id' => $pluginID,
                'date' => $date,
                'qty' => 1
            ))->save();
        }

        return $row;
    }

    /**
     * get stat row by plugin, platform, date
     * @param $platformID
     * @param $pluginID
     * @param $date
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function getByDate( $platformID, $pluginID, $date )
    {
        return $this->fetchRow(
            $this->select()
                ->from( $this->info( 'name' ) )
                ->where( 'platform_id = ?', $platformID )
                ->where( 'plugin_id = ?', $pluginID )
                ->where( 'date = ?', $date )
        );
    }


    /**
     * returns webhooks stats
     * @param null $platformID
     * @param null $pluginID
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getStats($platformID = null, $pluginID = null)
    {
        $select = $this->select()
            ->setIntegrityCheck( false )
            ->from(
                array ( 'ws' => $this->info( 'name' ) ),
                array ( 'SUM(ws.qty) AS amount, DATE_FORMAT( ws.date, \'%Y-%m-%d\' ) AS period' )
            )
			->group('ws.date')
            ->order('period')
        ;
        if ( $platformID ) {
            $select->where( 'ws.platform_id = ?', $platformID );
        }
        if ( $pluginID ) {
            $select->where( 'ws.plugin_id = ?', $pluginID );
        }
        return $this->fetchAll( $select );
    }

}
