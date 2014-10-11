<?php
	/**
	 * Cron statistics db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.1.3
	 */
	class Admin_Model_DbTable_CronStatistics extends D_Db_Table_Abstract
	{
		protected $_name = 'cron_statistics';

		/**
		 * Returns statistics for a given cron.
		 * @param integer $cronId - cron id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getForCron( $cronId )
		{
			return $this->fetchAll(
				$this->select()
					->where( 'cron_id = ?', $cronId )
					->order( 'id DESC' )
			);
		}

		/**
		 * Returns last cron task start date.
		 * @param integer $cronId - cron task id.
		 * @return strind
		 */
		public function getLastStart( $cronId )
		{
			$row = $this->fetchRow(
				$this->select()
					->where( 'cron_id = ?', $cronId )
					->order( 'id DESC' )
			);
			return $row ? $row->started : null;
		}

		/**
		 * Returns last cron task finish date.
		 * @param integer $cronId - cron task id.
		 * @return strind
		 */
		public function getLastFinish( $cronId )
		{
			$row = $this->fetchRow(
				$this->select()
					->where( 'cron_id = ?', $cronId )
					->where( 'finished IS NOT NULL' )
					->order( 'id DESC' )
			);
			return $row ? $row->finished : null;
		}

		/**
		 * Returns last cron task fail for certain period.
		 * @param integer $cronId - cron task id.
		 * @param integer $maxExecTime - max exec time in seconds.
		 * @return string
		 */
		public function getLastFail( $cronId, $maxExecTime )
		{
			$row = $this->fetchRow(
				$this->select()
					->where( 'cron_id = ?', $cronId )
					->where( '( started + INTERVAL ? SECOND ) < NOW()', $maxExecTime )
					->where( 'finished IS NULL' )
					->order( 'id DESC' )
			);
			return $row ? $row->started : null;
		}

		/**
		 * Returns failed runs of all cron tasks.
		 * @param integer $lastId - last notificated id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getFailed( $lastId )
		{
			$select = $this->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'cs' => $this->info( 'name' ) ),
					'*'
				)
				->joinLeft(
					array ( 'c' => Table::_( 'cron' )->info( 'name' ) ),
					'c.id = cs.cron_id',
					array ( 'name', 'max_exec_time' )
				)
				->where( '( started + INTERVAL max_exec_time SECOND ) < NOW()' )
				->where( 'finished IS NULL' )
				->order( 'id ASC' )
				;
			if ( $lastId ) {
				$select->where( 'cs.id > ?', $lastId );
			}
			return $this->fetchAll( $select );
		}

		/**
		 * Deletes old statistics.
		 */
		public function deleteOld()
		{
			$config = Config::getInstance();
			$rows = $this->fetchAll(
				$this->select()
					->from(
						array ( 'cs' => $this->info( 'name' ) ),
						array (
							'cron_id',
							'last_id' => new Zend_Db_Expr( 'MAX( `id` )' )
						)
					)
					->group( 'cron_id' )
			);
			if ( count( $rows ) ) {
				$ids = array ();
				foreach ( $rows as $row ) {
					$ids[] = $row->last_id;
				}
				$this->delete( array (
					'started < ( NOW() - INTERVAL '. $config->cron->actualPeriodInDays .' DAY )',
					'id NOT IN ( '. implode( ',', $ids ) .' )'
				) );
			}
		}
	}
