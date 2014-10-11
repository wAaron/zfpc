<?php
/**
 * Emails db table.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Model_DbTable_Emails extends D_Db_Table_StatusItems_Abstract
{
	protected $_name = 'emails';

	protected $_rowClass = 'Admin_Model_DbRow_Email';

	/**
	 * Returns an admin by id
	 * @param $id
	 * @return null|Zend_Db_Table_Row_Abstract
	 */
	public function getEmailById($id)
	{
		return $this->fetchRow(
			$this->select()
				->where( 'id = ?', $id )
		);
	}

	/**
	 * returns list of all emails by params
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getByParams($params)
	{

		$select = $this->select()
			->setIntegrityCheck( false )
			->from(
				array ( 'e' => $this->info( 'name' ) ),
				array ( '*' )
			)
			->joinLeft(
				array ( 'pn' => Table::_( 'plugins' )->info( 'name' ) ),
				'e.plugin_id = pn.id',
				array ( 'plugin' => 'pn.name' )
			)
			->joinLeft(
				array ( 'pm' => Table::_( 'platforms' )->info( 'name' ) ),
				'e.platform_id = pm.id',
				array ( 'platform' => 'pm.name' )
			)
			->order('e.id DESC');

		// Filter by platform.
		if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
			$select->where( 'e.platform_id = ?', $params['platform'] );
		}
		// Filter by plugin.
		if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
			$select->where( 'e.plugin_id = ?', $params['plugin'] );
		}
		// Filter by plugin.
		if ( isset ( $params['status'] ) && !empty ( $params['status'] ) ) {
			$select->where( 'e.status = ?', $params['status'] );
		}

		return $this->fetchAll($select);
	}

	/**
	 * save incoming email
	 * @param $data
	 */
	public function saveEmail($data)
	{
		$date = date(LOCAL_DATETIME_FORMAT);
		//save data
		$row = $this->createRow(array(
			'platform_id' => isset( $data['platform_id'] ) ? $data['platform_id'] : 0,
			'plugin_id' => isset( $data['plugin_id'] ) ? $data['plugin_id'] : 0,
			'priority' => isset( $data['emailPriority'] ) ? $data['emailPriority'] : 0,
			'callback_url' => isset($data['callbackUrl']) ? $data['callbackUrl']  : null,
			'status' => isset( $data['emailNotSend'] ) ? 'sent by app' : 'wait',
			'to' => $data['emailTo'],
			'from' => $data['emailFrom'],
			'from_name' => isset($data['emailFromName']) ?  $data['emailFromName']  : null,
			'subject' => $data['emailSubject'],
			'message' => $data['emailMessage'],
			'create_time' => $date,
			'sent_time' =>  isset( $data['emailNotSend'] ) ? $date : null
		));

		return $row->save();
	}


	/**
	 * get list of email which have to be sent
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getListToMailer($priority = 0)
	{
		$select = $this->select()
			->from(
				$this->info( 'name' ),
				array ( '*' )
			)
			->where('status = ?','wait')
			//emails with higher priority will be sent first
			->order('priority DESC');

		//add filter by priority
		if($priority){
			$select->where('priority >= ?', $priority);
		}

		return $this->fetchAll($select);
	}


	/**
	 * get stats by sent emails
	 * @param $platformId
	 * @param $pluginId
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	public function getStats( $platformId = null, $pluginId = 0 )
	{
		$select = $this->select()
			->setIntegrityCheck( false )
			->from(
				array ( $this->info( 'name' ) ),
				array ( 'COUNT( id ) AS amount, DATE_FORMAT( sent_time, \'%Y-%m-%d\' ) AS period' )
			)
			->where( 'status in (?)', array('sent','sent by app') )
			->group( 'period' )
		;
		if ( $platformId != null ) {
			$select->where( 'platform_id = ?', $platformId );
		}
		if ( $pluginId ) {
			$select->where( 'plugin_id = ?', $pluginId );
		}
		return $this->fetchAll( $select );
	}
}
