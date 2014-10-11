<?php
	/**
	 * The model of payment tables.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.4.8
	 */
	class Payment_Model_Payment
	{
		/**
		 * Table plans.
		 * @var Payment_Model_DbTable_Plans
		 */
		private $_tablePlans;

		/**
		 * Table prices.
		 * @var Payment_Model_DbTable_Prices
		 */
		private $_tablePrices;

		/**
		 * Table products.
		 * @var Payment_Model_DbTable_Products
		 */
		private $_tableProducts;

		/**
		 * Table sales.
		 * @var Payment_Model_DbTable_Sales
		 */
		private $_tableSales;

		/**
		 * Table settings.
		 * @var Payment_Model_DbTable_Settings
		 */
		private $_tableSettings;

		/**
		 * Table transactions.
		 * @var Payment_Model_DbTable_Transactions
		 */
		private $_tableTransactions;

		/**
		 * Table charges.
		 * @var Payment_Model_DbTable_Charges
		 */
		private $_tableCharges;

		/**
		 * Table charge bills.
		 * @var Payment_Model_DbTable_ChargesBills
		 */
		private $_tableChargesBills;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->_tablePlans = new Payment_Model_DbTable_Plans();
			$this->_tablePrices = new Payment_Model_DbTable_Prices();
			$this->_tableProducts = new Payment_Model_DbTable_Products();
			$this->_tableSales = new Payment_Model_DbTable_Sales();
			$this->_tableSettings = new Payment_Model_DbTable_Settings();
			$this->_tableTransactions = new Payment_Model_DbTable_Transactions();
			$this->_tableCharges = new Payment_Model_DbTable_Charges();
			$this->_tableChargesBills = new Payment_Model_DbTable_ChargesBills();
		}

		/**
		 * Returns a shop by name.
		 * @param string $platform - shop platform.
		 * @param string $name - shop name.
		 * @return Zend_Db_Table_Row
		 */
		public function getShop( $platform, $name )
		{
			$tableShops = new Default_Model_DbTable_Shops();
			$name = $tableShops->getAdapter()->quote( $name, 'string' );
			$platform = $tableShops->getAdapter()->quote( $platform, 'string' );
			$where = "`name` = $name AND `platform` = $platform";
			return $tableShops->fetchRow( $where );
		}

		/**
		 * Returns a plugin by name.
		 * @todo remove and replace calls with plugin table.
		 * @param string $platform - shop platform.
		 * @param string $name - plugin name.
		 * @return Zend_Db_Table_Row
		 */
		public function getPlugin( $platform, $name )
		{
			$tablePlugins = new Default_Model_DbTable_Plugins();
			$name = $tablePlugins->getAdapter()->quote( $name, 'string' );
			$platform = $tablePlugins->getAdapter()->quote( strtolower( $platform ), 'string' );
			$where = "`name` = $name AND `platform` = $platform";
			return $tablePlugins->fetchRow( $where );
		}

		/**
		 * Returns a plan by name.
		 * @param string $name - plan name.
		 * @return Zend_Db_Table_Row
		 */
		public function getPlan( $name ) {
			return $this->_tablePlans->fetchRow( array (
				'name = ?' => $name
			) );
		}

		/**
		 * Returns a list of platform plans for a plugin.
		 * Each plan has products with specific prices and options.
		 *
		 * @see $this->productsForPlan()
		 * @param integer $pluginId - plugin id.
		 * @param string $currentPlan - instance current plan.
		 * @return array
		 */
		public function plans( $pluginId, $currentPlan )
		{
			$plans = array ();
			$rows = Table::_( 'plans' )->getByPlugin( $pluginId, $currentPlan );
			if ( $rows ) {
				foreach ( $rows as $_plan ) {
					$optionsText = '';
					if ( $options = $this->optionsForPlan( $_plan->id ) ) {
						foreach ( $options as $_option ) {
							$optionsText .= '<p>'. $_option->name .': '. $_option->value .' '. $_option->unit .'</p>';
						}
					}
					$products = $this->productsForPlan( $_plan->payment_plan_id, $pluginId );
					$plans[] = array (
						'plan' => $_plan,
						'products' => $products,
						'options' => $options,
						'optionsText' => $optionsText
					);
				}
			}
			return $plans;
		}

		/**
		 * Returns products are related with given plugin.
		 * @see $this->plans()
		 * @param integer $planId - plan id.
		 * @param integer $pluginId - plugin id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function productsForPlan( $planId, $pluginId )
		{
			return $this->_tableProducts->fetchAll(
				$this->_tableProducts->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'prod' => $this->_tableProducts->info( 'name' ) ),
						array ( 'id', 'variety', 'quantity' )
					)
					->joinLeft(
						array ( 'price' => $this->_tablePrices->info( 'name' ) ),
						'price.product_id = prod.id',
						array ( 'price' )
					)
					->joinLeft(
						array ( 'plug' => Table::_( 'plugins' )->info( 'name' ) ),
						'plug.id = price.plugin_id',
						array ( 'plugin_id' => 'id' )
					)
					->where( 'price.payment_plan_id = ?', $planId )
					->having( 'plugin_id = ?', $pluginId )
			);
		}

		/**
		 * Returns plan options.
		 * @param integer $planId - plan id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function optionsForPlan( $planId ) {
			return Table::_( 'options' )->getForPlan( $planId );
		}

		/**
		 * Returns setting's value or sets it if value is passed.
		 * Settings can be for whole shop or for plugin in shop only.
		 *
		 * @param Zend_Db_Table_Row|integer $shop - shop.
		 * @param Zend_Db_Table_Row|integer $plugin - plugin.
		 * @param string $name - setting name.
		 * @param mixed $value - setting value.
		 * @return mixed
		 */
		public function setting( $shop, $plugin, $name, $value = null )
		{
			$shopId = ( $shop instanceof Zend_Db_Table_Row ) ? $shop->id : $shop;
			$pluginId = ( $plugin instanceof Zend_Db_Table_Row ) ? $plugin->id : $plugin;
			// Where.
			$where = "`shop_id` = $shopId";
			if ( $pluginId ) {
				$where .= " AND `plugin_id` = $pluginId";
			}
			$where .= " AND `name` = ". $this->_tableSettings->getAdapter()->quote( $name, 'string' );
			// Update.
			if ( $value !== null ) {
				if ( $this->_tableSettings->fetchRow( $where ) ) {
					return $this->_tableSettings->update( array (
						'value' => $value
					), $where );
				}
				else {
					return $this->_tableSettings->insert( array (
						'shop_id' => $shop->id,
						'plugin_id' => isset ( $plugin->id ) ? $plugin->id : null,
						'name' => $name,
						'value' => $value
					) );
				}
			}
			// Select.
			else {
				return $this->_tableSettings->fetchRow( $where )->value;
			}
		}

		/**
		 * Creates a new transaction record.
		 * @param integer $invoiceId - invoice id.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param mixed $plan - plan name or id.
		 * @param string $invoiceStatus - invoice status.
		 * @param string $fraudStatus - fraud status.
		 * @param integer $recurring - recurring sign.
		 * @param float $amount - amount.
		 * @param string $details - transaction details.
		 */
		public function saveTransaction( $invoiceId, $shopId, $pluginId, $plan, $productId, $invoiceStatus, $fraudStatus, $recurring, $amount, $details )
		{
			// TODO validation
			if ( is_numeric( $plan ) ) {
				$plan = $this->_tablePlans->get( $plan ); // TODO don't load just =
			}
			else if ( is_string( $plan ) ) {
				$plan = $this->getPlan( $plan );
			}
			$this->_tableTransactions->insert( array (
				'invoice_id' => $invoiceId,
				'shop_id' => $shopId,
				'plugin_id' => $pluginId,
				'payment_plan_id' => $plan->id,
				'product_id' => $productId,
				'invoice_status' => $invoiceStatus,
				'fraud_status' => $fraudStatus,
				'recurring' => $recurring,
				'amount' => $amount,
				'details' => $details
			) );
		}

		/**
		 * Returns last specific transaction.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param bool $actualOnly - accepted and not refunded.
		 * @return Zend_Db_Table_Row
		 */
		public function getLastTransaction( $shopId, $pluginId, $actualOnly = false )
		{
			$select = $this->_tableTransactions->select()
				->from( $this->_tableTransactions->info( 'name' ), '*' )
				->where( 'shop_id = ?', $shopId )
				->where( 'plugin_id = ?', $pluginId )
				->order( 'id DESC' )
				->limit( 1 )
				;
			if ( $actualOnly ) {
				$select->where( 'refunded = 0' )
					->where( 'fraud_status = \'pass\'' )
					->where( 'invoice_status != \'declined\'' )
					;
			}
			return $this->_tableTransactions->fetchRow( $select );
		}

		/**
		 * Returns a transaction by invoice id.
		 * @todo rename to getTransactionByInvoice
		 * @param integer $invoiceId - 2co invoice id.
		 * @return Zend_Db_Table_Row
		 */
		public function fetchTransactionInvoice( $invoiceId )
		{
			if ( is_numeric( $invoiceId ) ) {
				$where = "`invoice_id` = $invoiceId";
				return $this->_tableTransactions->fetchRow(
					$this->_tableTransactions->select()
						->from( $this->_tableTransactions->info( 'name' ), '*' )
						->where( $where )
						->limit( 1 )
				);
			}
			return false;
		}

		/**
		 * Saves a sale.
		 * @param integer $saleId - sale id.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @param integer $planId - plan id.
		 * @return bool
		 */
		public function insertSale( $saleId, $shopId, $pluginId, $planId )
		{
			if ( is_numeric( $saleId ) ) {
				return $this->_tableSales->insert( array (
					'sale_id' => $saleId,
					'shop_id' => $shopId,
					'plugin_id' => $pluginId,
					'payment_plan_id' => $planId
				) );
			}
			return false;
		}

		/**
		 * Returns a sale.
		 * @param integerg $saleId - sale id.
		 * @return Zend_Db_Table_Row
		 */
		public function fetchSale( $saleId )
		{
			if ( is_numeric( $saleId ) ) {
				return $this->_tableSales->fetchRow( "`sale_id` = $saleId" );
			}
			return false;
		}

		/**
		 * Changes invoice status.
		 * @param integer $invoiceId - invoice id.
		 * @param string $status - status.
		 * @return bool
		 */
		public function invoiceStatus( $invoiceId, $status )
		{
			if ( is_numeric( $invoiceId ) && $status ) {
				return $this->_tableTransactions->update(
					array (
						'invoice_status' => $status
					),
					"`invoice_id` = $invoiceId"
				);
			}
			return false;
		}

		/**
		 * Changes fraud status.
		 * @param integer $invoiceId - invoice id.
		 * @param string $status - status.
		 * @return bool
		 */
		public function fraudStatus( $invoiceId, $status )
		{
			if ( is_numeric( $invoiceId ) && $status ) {
				return $this->_tableTransactions->update(
					array (
						'fraud_status' => $status
					),
					"`invoice_id` = $invoiceId"
				);
			}
			return false;
		}

		/**
		 * Marks a transaction as recurring.
		 * @param integer $invoiceId - invoice id.
		 * @param integer $value - 1 or 0.
		 * @return bool
		 */
		public function recurring( $invoiceId, $value )
		{
			if ( is_numeric( $plan ) && is_numeric( $value ) ) {
				return $this->_tableTransactions->update(
					array (
						'recurring' => $value
					),
					"`invoice_id` = $invoiceId"
				);
			}
			return false;
		}

		/**
		 * Marks a transaction as refunded.
		 * @param integer $invoiceId - invoice id.
		 * @param integer $value - 1 or 0.
		 * @return bool
		 */
		public function refund( $invoiceId, $value )
		{
			if ( is_numeric( $invoiceId ) && is_numeric( $value ) ) {
				return $this->_tableTransactions->update(
					array (
						'refunded' => $value
					),
					"`invoice_id` = $invoiceId"
				);
			}
			return false;
		}

		/**
		 * Returns all plugin transactions.
		 * @param Zend_Db_Table_Row $shop - shop object.
		 * @param Zend_Db_Table_Row $plugin = plugin object.
		 * @return Zend_Db_Table_Rowset
		 */
		public function history( Zend_Db_Table_Row $shop, Zend_Db_Table_Row $plugin )
		{
			$where = "`shop_id` = {$shop->id} AND `plugin_id` = {$plugin->id}";
			return $this->_tableTransactions->fetchAll(
				$this->_tableTransactions->select()
					->from( $this->_tableTransactions->info( 'name' ), '*' )
					->where( $where )
					->order( "id DESC" )
			);
		}

		/**
		 * Returns last charge.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 * @return Payment_Model_DbRow_Charge
		 */
		public function getLastCharge( $shopId, $pluginId )
		{
			return $this->_tableCharges->fetchRow(
				$this->_tableCharges->select()
					->where( 'shop_id = ?', $shopId )
					->where( 'plugin_id = ?', $pluginId )
					->where( 'status = \'active\'' )
					->where( 'overdraft = 0' )
					->order( 'id DESC' )
					->limit( '1' )
			);
		}

		/**
		 * Returns all charge bills.
		 * @param integer $chargeId - charge id.
		 * @return Zend_Db_Table_Rowset
		 */
		public function getChargeHistory( $chargeId )
		{
			return $this->_tableChargesBills->fetchAll(
				$this->_tableChargesBills->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'cb' => $this->_tableChargesBills->info( 'name' ) ),
						'cb.*'
					)
					->join(
						array ( 'c' => $this->_tableCharges->info( 'name' ) ),
						'c.charge_id = cb.charge_id',
						array ( 'amount' )
					)
					->where( 'cb.charge_id = ?', $chargeId )
					->order( 'cb.id DESC' )
			);
		}

		/**
		 * Changes charges' statuses to deleted.
		 * @param integer $shopId - shop id.
		 * @param integer $pluginId - plugin id.
		 */
		public function deleteInstanceCharges( $shopId, $pluginId )
		{
			Table::_( 'charges' )->update( array (
				'status' => 'deleted'
			), array (
				'shop_id = ?' => $shopId,
				'plugin_id = ?' => $pluginId
			) );
		}

		/**
		 * Returns combined payment history of all equipped payment systems.
		 * @param array $params - filter parameters.
		 * @return Zend_Db_Table_Rowset
		 */
		public function combinedHistory( $params )
		{
			$db = Zend_Registry::get( 'db' );
			$platforms = Table::_( 'platforms' )->fetchAll();
			$withoutTransactions = $withoutCharges = false;
			$platformByName = array ();
			foreach ( $platforms as $platform ) {
				$platformByName[ $platform->name ] = $platform;
			}
			// Prepare select for transaction table.
			$selectTransactions = $this->_tableTransactions->select()
				->setIntegrityCheck( false )
				->from(
					array ( 't' => $this->_tableTransactions->info( 'name' ) ),
					array (
						'platform_id' => 'pl.id', 'p.platform', 'id', 'invoice_id', 'shop' => 's.name', 's.email',
						'user' => 'u.name', 'plugin' => 'p.name', 'invoice_status', 'recurring', 'amount', 'date', 'details'
					)
				)
				->joinLeft(
					array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
					'p.id = t.plugin_id',
					array ()
				)
				->joinLeft(
					array ( 'pl' => Table::_( 'platforms' )->info( 'name' ) ),
					'pl.name = p.platform',
					array ()
				)
				->joinLeft(
					array ( 's' => Table::_( 'shops' )->info( 'name' ) ),
					's.id = t.shop_id',
					array ()
				)
				->joinLeft(
					array ( 'u' => Table::_( 'users' )->info( 'name' ) ),
					'u.shop_id = t.shop_id',
					array ()
				);
			// Prepare select for charge table.
			$selectCharges = $this->_tableCharges->select()
				->setIntegrityCheck( false )
				->from(
					array ( 'c' => $this->_tableCharges->info( 'name' ) ),
					array (
						'platform_id' => new Zend_Db_Expr( $platformByName['shopify']->id ),
						'platform' => new Zend_Db_Expr( '\'shopify\'' ),
						'cb.id', 'invoice_id' => 'charge_id', 'shop' => 's.name', 's.email', 'user' => 'u.name',
						'plugin' => 'p.name', 'invoice_status' => 'status', 'recurring', 'cb.amount', 'cb.date', 'details'
					)
				)
				->join(
					array ( 'cb' => Table::_( 'chargesBills' )->info( 'name' ) ),
					'cb.charge_id = c.charge_id',
					array ()
				)
				->joinLeft(
					array ( 'p' => Table::_( 'plugins' )->info( 'name' ) ),
					'p.id = c.plugin_id',
					array ()
				)
				->joinLeft(
					array ( 's' => Table::_( 'shops' )->info( 'name' ) ),
					's.id = c.shop_id',
					array ()
				)
				->joinLeft(
					array ( 'u' => Table::_( 'users' )->info( 'name' ) ),
					'u.shop_id = c.shop_id',
					array ()
				);
			// Filter by start date.
			if ( isset ( $params['start_date'] ) && !empty ( $params['start_date'] ) ) {
				$date = new DateTime( $params['start_date'] );
				$selectTransactions->where( 'date > ?', $date->format( 'Y-m-d' ) );
				$selectCharges->where( 'date > ?', $date->format( 'Y-m-d' ) );
			}
			// Filter by end date.
			if ( isset ( $params['end_date'] ) && !empty ( $params['end_date'] ) ) {
				$date = new DateTime( $params['end_date'] );
				$selectTransactions->where( 'date < ?', $date->format( 'Y-m-d' ) );
				$selectCharges->where( 'date < ?', $date->format( 'Y-m-d' ) );
			}
			// Filter by platform.
			if ( isset ( $params['platform'] ) && !empty ( $params['platform'] ) ) {
				if ( ( $params['platform'] == $platformByName['shopify']->id ) && ( $platformByName['shopify']->payment == 'outer' ) ) {
					$withoutTransactions = true;
				} else {
					$selectTransactions->having( 'platform_id = ?', $params['platform'] );
					$withoutCharges = true;
				}
			}
			// Filter by plugin.
			if ( isset ( $params['plugin'] ) && !empty ( $params['plugin'] ) ) {
				$selectTransactions->where( 'plugin_id = ?', $params['plugin'] );
				$selectCharges->where( 'plugin_id = ?', $params['plugin'] );
			}
			// Filter by user.
			if ( isset ( $params['user'] ) && !empty ( $params['user'] ) ) {
				$selectTransactions->having( 'user LIKE ?', '%'. $params['user'] .'%' );
				$selectCharges->having( 'user LIKE ?', '%'. $params['user'] .'%' );
			}
			// Union elements.
			$union = array ();
			if ( !$withoutTransactions ) {
				$union[] = $selectTransactions;
			}
			if ( !$withoutCharges ) {
				$union[] = $selectCharges;
			}
			// Fetch.
			$db->setFetchMode( Zend_Db::FETCH_OBJ );
			return $db->fetchAll(
				$db->select()
					->union( $union )
					->order( 'date DESC' )
			);
		}
	}
