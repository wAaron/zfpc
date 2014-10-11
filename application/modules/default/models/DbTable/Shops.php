<?php
	/**
	 * Shops db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.1.2
	 */
	class Default_Model_DbTable_Shops extends D_Db_Table_Abstract
	{
		protected $_name = 'shops';

		/**
		 * Establishes a shop object.
		 * If given shop doesn't exist, creates a new one.
		 *
		 * @param string $name - shop name.
		 * @param string $platform - platform.
		 * @param string $email - shop email.
		 * @return Zend_Db_Table_Row
		 */
		public function establish( $name, $platform, $email = '' )
		{
			$quotedName = $this->getAdapter()->quote( $name, 'string' );
			$quotedPlatform = $this->getAdapter()->quote( $platform, 'string' );
			$where = "`name` = $quotedName AND `platform` = $quotedPlatform";
			if ( !$shop = $this->fetchRow( $where ) ) {
				$this->insert( array (
					'name' => $name,
					'platform' => $platform,
					'email' => $email
				) );
				$shop = $this->fetchRow( $where );
			}
			return $shop;
		}

		/**
		 * Checks out whether a shop exists or not.
		 * @param string $name - shop name.
		 * @param string $platform - platform.
		 * @return bool
		 */
		public function exists( $name, $platform )
		{
			$quotedName = $this->getAdapter()->quote( $name, 'string' );
			$quotedPlatform = $this->getAdapter()->quote( $platform, 'string' );
			$where = "`name` = $quotedName AND `platform` = $quotedPlatform";
			if ( $shop = $this->fetchRow( $where ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Returns a shop is assigned to a user.
		 * @param integer $userId - user id.
		 * @return Zend_Db_Table_Row
		 */
		public function getForUser( $userId )
		{
			$tableUsers = new Default_Model_DbTable_Users();
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 's' => $this->info( 'name' ) ),
						array ( 's.*' )
					)
					->joinLeft(
						array ( 'u' => $tableUsers->info( 'name' ) ),
						"u.shop_id = s.id",
						array ()
					)
					->where( 'u.id = ?', $userId )
					->limit( 1 )
			);
		}

		/**
		 * Returns a shop by e-mail.
		 * @param string $email - e-mail.
		 * @return Zend_Db_Table_Row
		 */
		public function getByEmail( $email, $platform = null )
		{
			$select = $this->select()
				->where( 'email = ?', $email );
			if ( $platform ) {
				$select->where( 'platform = ?', $platform );
			}
			return $this->fetchRow( $select );
		}

		/**
		 * Returns a shop by hash.
		 * @param string $hashl - hash.
		 * @return Zend_Db_Table_Row
		 */
		public function getByHash( $hash )
		{
			return $this->fetchRow(
				$this->select()
					->where( 'name like ?', '%'. $hash .'%' )
			);
		}
	}
