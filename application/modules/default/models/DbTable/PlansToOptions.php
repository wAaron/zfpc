<?php
	/**
	 * Plans to options db table.
	 *
	 * @author Polyakov Ivan, SpurIT <contact@spur-i-t.com>
	 * @copyright Copyright (c) 2012 SpurIT <contact@spur-i-t.com>, All rights reserved
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.0
	 */
	class Default_Model_DbTable_PlansToOptions extends D_Db_Table_Abstract
	{
		protected $_name = 'plugins_plans_to_options';

		protected $_primary = array ( 'plan_id', 'option_id' );
	}
