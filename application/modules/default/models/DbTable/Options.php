<?php
	/**
	 * Options db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.1.3
	 */
	class Default_Model_DbTable_Options extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_plans_options';

		/**
		 * Returns a list for admin side.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getAdminList()
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'o' => $this->info( 'name' ) ),
						array ( '*' )
					)
					->joinLeft(
						array ( 'pto' => Table::_( 'plansToOptions' )->info( 'name' ) ),
						'pto.option_id = o.id',
						array ( 'plan_id' )
					)
					->group( 'o.id' )
			);
		}

		/**
		 * Returns options assigned to plugin's plan.
		 * Doesn't select options marked as not for display.
		 *
		 * @param integer $planId - plan id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getForPlan( $planId )
		{
			return $this->fetchAll(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'o' => $this->info( 'name' ) ),
						'o.*'
					)
					->join(
						array ( 'pto' => Table::_( 'plansToOptions' )->info( 'name' ) ),
						'pto.option_id = o.id',
						array ( 'plan_id' )
					)
					->where( 'pto.plan_id = ?', $planId )
					->where( 'o.display = 1' )
			);
		}

		/**
		 * Returns an option assigned to plugin's plan by key.
		 * @param integer $planId - plan id.
		 * @param string $key - option key.
		 * @return Zend_Db_Table_Row
		 */
		public function getForStatistics( $planId, $key )
		{
			return $this->fetchRow(
				$this->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'o' => $this->info( 'name' ) ),
						'o.*'
					)
					->join(
						array ( 'pto' => Table::_( 'plansToOptions' )->info( 'name' ) ),
						'pto.option_id = o.id',
						array ( 'plan_id' )
					)
					->where( 'pto.plan_id = ?', $planId )
					->where( 'o.key = ?', $key )
			);
		}
	}
