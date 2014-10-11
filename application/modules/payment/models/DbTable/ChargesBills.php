<?php
	/**
	 * Payment charges bills db table.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.1.2
	 */
	class Payment_Model_DbTable_ChargesBills extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_charges_bills';

		/**
		 * Insert new bill of recurring charge.
		 * @param object $charge - charge.
		 */
		public function bill( $charge )
		{
			$config = Config::getInstance();
			$date = date( 'Y-m-d H:i:s', strtotime( $charge->billing_on ) );
			$select = $this->select()
				->where( 'charge_id = ?', $charge->id )
				->where( 'date = ?', $date )
				;
			if ( !$this->fetchRow( $select ) ) {
				$this->insert( array (
					'charge_id' => $charge->id,
					'amount' => round( $charge->price - ( ( $charge->price / 100 ) * $config->plugin->shopify->comission ), 2 ),
					'date' => $date
				) );
			}
		}

		/**
		 * Check if bill with billing_on date exists.
		 * @param object $charge - charge.
		 * @return Zend_Db_Table_Row
		 */
		public function hasBill( $charge )
		{
			$date = date( 'Y-m-d H:i:s', strtotime( $charge->billing_on ) );
			return $this->fetchRow(
				$this->select()
					->where( 'charge_id = ?', $charge->id )
					->where( 'date = ?', $date )
			);
		}
	}
