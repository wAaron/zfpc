<?php
	/**
	 * DbRow of plugin detail DbTable.
	 *
	 * @author Kuksanau Ihnat
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.1
	 */
	class Default_Model_DbRow_PluginDetail extends Zend_Db_Table_Row
	{
		/**
		 * compare hmac header with encoded app client_secret
		 * @param $data
		 * @param $hmac
		 * @return bool
		 */
		public function verifyWebhook($hmac, $data)
		{
			$result = false;
			//get hash string
			$hashCode = trim(base64_encode(hash_hmac(
				"sha256",
				$data,
				$this->client_secret,
				true
			)));
			//compare
			if( $hmac == $hashCode ){
				$result = true;
			}

			return $result;
		}
	}
