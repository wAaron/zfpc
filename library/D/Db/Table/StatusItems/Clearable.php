<?php
/**
 * To clear old items from db table time by time
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Application
 * @version 1.0.0
 */
interface D_Db_Table_StatusItems_Clearable
{
    /**
     * get expired
     * @param $days
     * @param string $status
     * @return mixed
     */
    public function getOldItems( $days, $status = 'sent' );

    /**
     * delete expired items
     * @param $days
     * @param string $status
     * @return mixed
     */
    public function deleteOldItems( $days, $status = 'sent' );

}