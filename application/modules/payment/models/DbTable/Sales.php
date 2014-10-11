<?php
	/**
	 * Payment sales db table.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.0.0
	 */
	class Payment_Model_DbTable_Sales extends D_Db_Table_Abstract
	{
		protected $_name = 'payment_sales';

		protected $_primary = 'sale_id';
	}