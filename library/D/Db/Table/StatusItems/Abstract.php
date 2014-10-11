<?php
/**
 * Base class for tables, which have column status, date and have to be clear time to time.
 * Contains common methods.
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Application
 * @version 1.0.0
 */
abstract class D_Db_Table_StatusItems_Abstract extends D_Db_Table_Abstract implements D_Db_Table_StatusItems_Clearable
{
    /**
     * id field name
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * status field name
     * @var string
     */
    protected $_statusFieldName = 'status';

    /**
     * date field name
     * @var string
     */
    protected $_dateFieldName = 'sent_time';

    /**
     * delete items which are expired
     * @param $daysExpire
     * @param string $status
     * @return int|mixed
     */
    public function deleteOldItems( $daysExpire, $status = 'sent' )
    {
        $deletedQty = 0;
        //get items which are expired
        $oldItems = $this->getOldItems( $daysExpire, $status );
        if(count($oldItems) > 0){
            //prepare list of ids to delete
            $ids = array();
            foreach ( $oldItems as $item){
                $ids[] = $item->{$this->_idFieldName};
            }

            $where = $this->getAdapter()->quoteInto($this->_idFieldName.' IN (?)', $ids);
            //delete
            $deletedQty = $this->delete($where);
        }

        return $deletedQty;
    }

    /**
     * get expired emails
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getOldItems($daysExpire, $status = 'sent')
    {
        $select = $this->select()
            ->from(
                $this->info( 'name' ),
                array('*')
            )
            ->where($this->_dateFieldName.' <= NOW()-INTERVAL ? DAY', $daysExpire);

        //add filter by status
        if(is_array($status) ){
            $select->where($this->_statusFieldName." IN (?)", $status);
        }else{
            $select->where($this->_statusFieldName." = ?", $status);
        }

        return $this->fetchAll( $select );
    }

}