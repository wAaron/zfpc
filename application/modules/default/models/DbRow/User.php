<?php
	/**
	 * DbRow of users DbTable.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.2.1
	 */
	class Default_Model_DbRow_User extends Zend_Db_Table_Row
	{
		/**
		 * Returns user setting.
		 * @param string $name - setting name.
		 * @param integer $externalId - external id.
		 * @return Zend_Db_Table_Row
		 */
		public function getSetting( $name, $externalId = null )
		{
			$tableUsersSettings = new Default_Model_DbTable_UsersSettings();
			$adapter = $tableUsersSettings->getDefaultAdapter();
			$name = $adapter->quote( $name, 'string' );
			$where = '';
			if ( $externalId ) {
				$externalId = (integer) $externalId;
				$where = "AND `external_id` = {$externalId}";
			}
			$where = "`user_id` = {$this->id} {$where} AND `name` = {$name}";
			return $tableUsersSettings->fetchRow( $where );
		}

		/**
		 * Sets user setting.
		 * @param string $name - setting name.
		 * @param mixed $value - setting value.
		 * @param integer $externalId - external id.
		 */
		public function setSetting( $name, $value, $externalId = null )
		{
			$tableUsersSettings = new Default_Model_DbTable_UsersSettings();
			$adapter = $tableUsersSettings->getDefaultAdapter();
			$name = $adapter->quote( $name, 'string' );
			$where = '';
			if ( $externalId ) {
				$externalId = (integer) $externalId;
				$where = "AND `external_id` = {$externalId}";
			}
			$where = "`user_id` = {$this->id} {$where} AND `name` = {$name}";
			$tableUsersSettings->update( array (
				'value' => $value,
			), $where );
		}

		/**
		 * Returns user total pay amount.
		 * @return integer
		 */
		public function getTotalPayAmount()
		{
			if ( isset ( $this->shop_id ) ) {
				$amount = Table::_( 'transactions' )->totalPaymentAmount( $this->shop_id );
				$amount += Table::_( 'charges' )->totalPaymentAmount( $this->shop_id );
				return round( $amount, 2 );
			}
		}

		/**
		 * Returns installed plugins by a user.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getInstalledPlugins() {
			if ( isset ( $this->shop_id ) ) {
				return Table::_( 'plugins' )->getInstalledPluginsByUser( $this->shop_id );
			}
		}
	}
