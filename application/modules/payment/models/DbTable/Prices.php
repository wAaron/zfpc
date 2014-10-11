<?php
	/**
	 * Payment prices db table.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.0.0
	 */
	class Payment_Model_DbTable_Prices extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_prices';

		protected $_primary = array ( 'plan_id', 'product_id' );
	}