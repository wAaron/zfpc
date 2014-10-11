<?php
	/**
	 * DbRow of plugin_plans DbTable.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.1.1
	 */
	class Default_Model_DbRow_Plan extends Zend_Db_Table_Row
	{
		/**
		 * Checks out if a plan is free.
		 * @return bool
		 */
		public function isFree() {
			return (bool) $this->is_free;
		}

		/**
		 * Returns plan name.
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Returns plan products.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getProducts() {
			return Model::_( 'payment' )->productsForPlan( $this->payment_plan_id, $this->plugin_id );
		}

		/**
		 * Returns plan options.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getOptions() {
			return Model::_( 'payment' )->optionsForPlan( $this->id );
		}
	}
