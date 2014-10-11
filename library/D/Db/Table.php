<?php
	/**
	 * DbTable container.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Library
	 * @version 1.0.5
	 */
	class Table
	{
		/**
		 * Table 'key-class name' binds.
		 * @var array
		 */
		static private $_tables = array (
			'admins' => 'Admin_Model_DbTable_Admins',
			'amazon_verifications' => 'Default_Model_DbTable_AmazonVerifications',
			'charges' => 'Payment_Model_DbTable_Charges',
			'chargesBills' => 'Payment_Model_DbTable_ChargesBills',
			'config' => 'Admin_Model_DbTable_Config',
			'credentials' => 'Default_Model_DbTable_Credentials',
			'cron' => 'Admin_Model_DbTable_Cron',
			'cronStat' => 'Admin_Model_DbTable_CronStatistics',
			'emails' => 'Admin_Model_DbTable_Emails',
			'instances' => 'Default_Model_DbTable_InstalledPlugins',
			'levels' => 'Admin_Model_DbTable_AccessLevels',
			'mailchimpLists' => 'Admin_Model_DbTable_MailchimpLists',
			'notifications' => 'Default_Model_DbTable_Notifications',
			'options' => 'Default_Model_DbTable_Options',
			'optionStat' => 'Default_Model_DbTable_OptionStatistics',
			'paymentPlans' => 'Payment_Model_DbTable_Plans',
			'paymentPrices' => 'Payment_Model_DbTable_Prices',
			'paymentProducts' => 'Payment_Model_DbTable_Products',
			'paymentSettings' => 'Payment_Model_DbTable_Settings',
			'permissions' => 'Admin_Model_DbTable_AccessPermissions',
			'plans' => 'Default_Model_DbTable_Plans',
			'plansToOptions' => 'Default_Model_DbTable_PlansToOptions',
			'platforms' => 'Default_Model_DbTable_Platforms',
			'plugins' => 'Default_Model_DbTable_Plugins',
			'pluginDetails' => 'Default_Model_DbTable_PluginDetails',
			'resources' => 'Admin_Model_DbTable_AccessResources',
			'servers' => 'Admin_Model_DbTable_Servers',
			'shops' => 'Default_Model_DbTable_Shops',
			'transactions' => 'Payment_Model_DbTable_Transactions',
			'users' => 'Default_Model_DbTable_Users',
			'userSettings' => 'Default_Model_DbTable_UsersSettings',
			'variables' => 'Default_Model_DbTable_Variables',
			'webhooks' => 'Webhooks_Model_DbTable_Webhooks',
			'webhooksInc' => 'Webhooks_Model_DbTable_WebhooksInc',
			'webhooksOut' => 'Webhooks_Model_DbTable_WebhooksOut',
			'webhooksStats' => 'Webhooks_Model_DbTable_WebhooksStats',
		);

		private function __construct() {}

		/**
		 * Returns table object is bound with given key.
		 * @param string $key - table key.
		 * @return Zend_Db_Table
		 */
		static public function _( $key )
		{
			if ( array_key_exists( $key, self::$_tables ) ) {
				if ( !Zend_Registry::isRegistered( 'table_' . $key ) ) {
					Zend_Registry::set( 'table_' . $key, new self::$_tables[ $key ] );
				}
				return Zend_Registry::get( 'table_' . $key );
			}
		}
	}
