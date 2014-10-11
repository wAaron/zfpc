<?php
	/**
	 * Plugin Center statistics.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.5.9
	 */
	class Admin_StatisticsController extends D_Admin_Controller_Abstract
	{
		/**
		 * Plugins statistics.
		 */
		public function pluginsAction()
		{
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'plugin statistics' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Detailed plugins information.
		 * @internal AJAX action.
		 */
		public function pluginGeneralAction()
		{
			$total = array (
				'totalEarned' => 0,
				'previousMonthRevenue' => 0,
				'currentMonthEarned' => 0,
				'currentMonthPlanned' => 0,
				'nextMonthPlanned' => 0,
				'installed' => 0,
				'uninstalled' => 0
			);
			$platform = $this->_getParam( 'platform' );
			$plugins = Table::_( 'plugins' )->getByPlatform( $platform )->toArray();
			foreach ( $plugins as &$_plugin ) {
				// Common data.
				$total['installed'] += $_plugin['installed'] = count(
					Table::_( 'instances' )->instances( array (
						'plugin_id' => $_plugin['id'],
						'state' => 'installed'
					), false )
				);
				$total['uninstalled'] += $_plugin['uninstalled'] = count(
					Table::_( 'instances' )->instances( array (
						'plugin_id' => $_plugin['id'],
						'state' => 'uninstalled'
					), false )
				);
				// Main payment.
				if ( $_plugin['payment'] == 'inner' ) {
					$total['totalEarned'] += $_plugin['totalEarned'] = Table::_( 'transactions' )->totalPluginEarned( $_plugin['id'] );
					$total['previousMonthRevenue'] += $_plugin['previousMonthRevenue'] = Table::_( 'transactions' )->previousMonthRevenue( $_plugin['id'] );
					$total['currentMonthEarned'] += $_plugin['currentMonthEarned'] = Table::_( 'transactions' )->currentMonthEarned( $_plugin['id'] );
					$total['currentMonthPlanned'] += $_plugin['currentMonthPlanned'] = $this->_calculatePlannedRevenue( $_plugin, 'current' );
					$total['nextMonthPlanned'] += $_plugin['nextMonthPlanned'] = $this->_calculatePlannedRevenue( $_plugin, 'next' );
				} else {
					switch ( $_plugin['platform'] ) {
						// Shopify payment.
						case 'shopify':
							$total['totalEarned'] += $_plugin['totalEarned'] = Table::_( 'charges' )->totalPluginEarned( $_plugin['id'] );
							$total['previousMonthRevenue'] += $_plugin['previousMonthRevenue'] = Table::_( 'charges' )->previousMonthRevenue( $_plugin['id'] );
							$total['currentMonthEarned'] += $_plugin['currentMonthEarned'] = Table::_( 'charges' )->currentMonthEarned( $_plugin['id'] );
							$total['currentMonthPlanned'] += $_plugin['currentMonthPlanned'] = $this->_calculateShopifyPlannedRevenue( $_plugin, 'current' );
							$total['nextMonthPlanned'] += $_plugin['nextMonthPlanned'] = $this->_calculateShopifyPlannedRevenue( $_plugin, 'next' );
							break;
					}
				}
			}
			// View.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->platforms = Table::_( 'platforms' )->fetchAll();
			$this->view->platform = $platform;
			$this->view->plugins = $plugins;
			$this->view->total = $total;
		}

		/**
		 * Calculates planned revenue for some month.
		 * @param array $plugin - plugin wit extended data.
		 * @param string $month - calculation for this month.
		 * @return integer
		 */
		private function _calculatePlannedRevenue( $plugin, $month )
		{
			// Make a list of transactions and instances which are already a part of an earned amount.
			$instanceIds = $transactionIds = array ();
			$earnedInstances = Table::_( 'transactions' )->currentMonthEarnedInstances( $plugin['id'] )->toArray();
			if ( $earnedInstances ) {
				foreach ( $earnedInstances as $_earned ) {
					$transactionIds[] = $_earned['id'];
					$instanceIds[] = $_earned['shop_id'] .'_'. $_earned['plugin_id'];
				}
			}
			// Transaction calculation.
			$previousMonthRecurrentTransactionAmount = Table::_( 'transactions' )->previousMonthRecurrentTransactionAmount( $plugin['id'] );
			$methodName = $month . 'MonthPlannedTransactionAmount';
			$recurrentTransactionAmount = Table::_( 'transactions' )->$methodName( $plugin['id'], $transactionIds );
			if ( $month == 'next' ) {
				$currentMonthRecurrentTransactionAmount = Table::_( 'transactions' )->currentMonthRecurrentTransactionAmount( $plugin['id'] );
				$recurrentTransactionAmount -= $currentMonthRecurrentTransactionAmount;
			}
			// A planned amount.
			$plannedAmount = (
				$plugin['previousMonthRevenue'] + $plugin['currentMonthEarned']
				- $previousMonthRecurrentTransactionAmount + $recurrentTransactionAmount
				+ $this->_calculateNewInstancesAmount( $plugin, $instanceIds, $month )
			);
			// Return.
			return $plannedAmount;
		}

		/**
		 * Calculates planned revenue for some month for Shopify platform.
		 * @param array $plugin - plugin wit extended data.
		 * @param string $month - calculation for this month.
		 * @return integer
		 */
		private function _calculateShopifyPlannedRevenue( $plugin, $month )
		{
			// Make a list of charges and instances which are already a part of an earned amount.
			$instanceIds = array ();
			$earnedInstances = Table::_( 'charges' )->currentMonthEarnedInstances( $plugin['id'] )->toArray();
			if ( $earnedInstances ) {
				foreach ( $earnedInstances as $_earned ) {
					$instanceIds[] = $_earned['shop_id'] .'_'. $_earned['plugin_id'];
				}
			}
			// A planned amount.
			$plannedAmount = (
				$plugin['previousMonthRevenue'] + $plugin['currentMonthEarned']
				+ $this->_calculateNewInstancesAmount( $plugin, $instanceIds, $month )
			);
			// Return.
			return $plannedAmount;
		}

		/**
		 * Calculates planned revenue for new instances.
		 * @param array $plugin - plugin data.
		 * @param array $instanceIds - already earned instances.
		 * @param string $month - month.
		 * @return integer
		 */
		private function _calculateNewInstancesAmount( $plugin, $instanceIds, $month )
		{
			$instances = Table::_( 'instances' )->instances( array (
				'plugin_id' => $plugin['id'],
				'state' => 'installed',
				'period' => date( 'Y-m' )
			), false );
			if ( $month == 'next' ) {
				$currentMonthInstances = $instances;
				$nextMonthInstances = Table::_( 'instances' )->instances( array (
					'plugin_id' => $plugin['id'],
					'state' => 'installed',
					'period' => date( 'Y-m', strtotime( 'NOW +1 month' ) )
				), false );
				$instances = array ();
				foreach ( $currentMonthInstances as $_instance ) {
					$instances[] = $_instance;
				}
				foreach ( $nextMonthInstances as $_instance ) {
					$instances[] = $_instance;
				}
			}
			$newInstancesAmount = 0;
			if ( count( $instances ) ) {
				foreach ( $instances as $_instance ) {
					// Skip already earned.
					if ( in_array( $_instance->shop_id .'_'. $_instance->plugin_id, $instanceIds ) ) {
						continue;
					}
					// Instance data selecting.
					$trialPeriod = Model::_( 'payment' )->setting( $_instance->shop_id, $_instance->plugin_id, 'trial period' );
					$currentPlan = Model::_( 'payment' )->setting( $_instance->shop_id, $_instance->plugin_id, 'current plan' );
					$basePlanName = $this->getHelper( $plugin['platform'] )
						->getBasePlan( $currentPlan );
					$basePlan = Model::_( 'payment' )->getPlan( $basePlanName );
					// Skip free.
					$plan = Table::_( 'plans' )->getPlan( $_instance->plugin_id, $currentPlan );
					if ( $plan->isFree() ) continue;
					// Add instance to an amount if trial ends in current month.
					if ( $trialPeriod && ( strtotime( "{$_instance->installation_date} +{$trialPeriod} days" ) >= time() ) ) {
						$products = Model::_( 'payment' )->productsForPlan( $basePlan->id, $_instance->plugin_id );
						$newInstancesAmount += $products->getRow( 0 )->price;
					}
				}
			}
			return $newInstancesAmount;
		}

		/**
		 * Detailed information about plugin's instances.
		 * @internal AJAX action.
		 */
		public function pluginDetailedAction()
		{
			$config = Config::getInstance();
			$cache = Zend_Registry::get( 'cache' );
			// Parameters.
			$pluginId = (integer) $this->_getParam( 'plugin_id' );
			$shop = base64_decode( $this->_getParam( 'shop' ) );
			$email = base64_decode( $this->_getParam( 'email' ) );
			$page = (integer) $this->_getParam( 'page', 1 );
			// Selecting.
			$instances = $paginator = null;
			$instancesCacheKey = 'admin_statistics_detailed_instances_' . $pluginId;
			if ( !empty ( $shop ) || !empty ( $email ) ) {
				$instances = Table::_( 'instances' )->instances( array (
					'plugin_id' => $pluginId,
					'shop' => $shop,
					'email' => $email
				) )->toArray();
			}
			if ( !$instances ) {
				if ( !$cache->test( $instancesCacheKey ) ) {
					$instances = Table::_( 'instances' )->instances( array (
						'plugin_id' => $pluginId
					) )->toArray();
					$cache->save( $instances, $instancesCacheKey );
				} else {
					$instances = $cache->load( $instancesCacheKey );
				}
			}
			// Processing.
			if ( count( $instances ) ) {
				// Paginator.
				Zend_View_Helper_PaginationControl::setDefaultViewPartial( 'ajax_pagination.phtml' );
				$paginator = Zend_Paginator::factory( $instances );
				$paginator->setItemCountPerPage(
					$config->plugin->center->admin->itemsPerPage
				);
				$paginator->setCurrentPageNumber( $page );
				$instances = $paginator->getItemsByPage( $page );
				// Extended selecting.
				$plugin = Table::_( 'plugins' )->get( $pluginId );
				$platform = Table::_( 'platforms' )->get( $plugin->platform );
				foreach ( $instances as &$_instance ) {
					$currentPlan = Model::_( 'payment' )->setting( $_instance['shop_id'], $_instance['plugin_id'], 'current plan' );
					$trialPeriod = Model::_( 'payment' )->setting( $_instance['shop_id'], $_instance['plugin_id'], 'trial period' );
					$plan = Table::_( 'plans' )->getPlan( $_instance['plugin_id'], $currentPlan );
					$isFree = $plan->isFree();
					$isPaid =
					(
						( $isFree || ( time() < strtotime( $_instance['paid_till'] ) ) )
						&& !Table::_( 'optionStat' )->overdraftUnpaidPeriod( $_instance['shop_id'], $_instance['plugin_id'] )
						&& ( $_instance['state'] == 'installed' )
					);
					$isTrial = ( time() - strtotime( $_instance['installation_date'] ) ) < ( $trialPeriod * SECONDS_PER_DAY ) ? true : false;
					$yes = Zend_Registry::get( 'translate' )->_( 'yes' );
					$no = Zend_Registry::get( 'translate' )->_( 'no' );
					$_instance['active'] = $isPaid ? $yes : $no;
					$_instance['trial'] = $isTrial && !$isFree ? $yes : $no;
					$_instance['free'] = $isFree ? $yes : $no;
					$_instance['lastPaymentDate'] = $_instance['lastPaymentAmount'] = $_instance['nextPaymentDate'] = $_instance['totalPaymentAmount'] = null;
					if ( $platform->payment == 'inner' ) {
						$transactionTableName = 'transactions';
					} else {
						switch ( $platform->name ) {
							case 'shopify';
								$transactionTableName = 'charges';
								break;
						}
					}
					$lastPayment = Table::_( $transactionTableName )->lastPayment( $_instance['shop_id'], $_instance['plugin_id'] );
					if ( $lastPayment ) {
						$_instance['lastPaymentDate'] = $lastPayment->date;
						$_instance['nextPaymentDate'] = $_instance['paid_till'];
						$_instance['lastPaymentAmount'] = round( $lastPayment->amount, 2 );
						$totalPaymentAmount = Table::_( $transactionTableName )->totalPaymentAmount( $_instance['shop_id'], $_instance['plugin_id'] );
						$_instance['totalPaymentAmount'] = round( $totalPaymentAmount, 2 );
					}
					else if ( $isTrial && !$isFree && ( $_instance['state'] == 'installed' ) ) {
						$_instance['nextPaymentDate'] = $_instance['paid_till'];
					}
				}
			}
			// Prepare view.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->formFilter = new Admin_Form_Statistics_InstanceFilter();
			$this->view->instances = $instances;
			$this->view->paginator = $paginator;
			$this->view->pluginId = $pluginId;
			$this->view->page = $page;
		}

		/**
		 * Instance setting editing.
		 * @internal AJAX action.
		 */
		public function instanceSettingsAction()
		{
			// Instance.
			$id = $this->_getParam( 'id' );
			$instance = Table::_( 'instances' )->get( $id );
			$plugin = Table::_( 'plugins' )->get( $instance->plugin_id );
			$platform = Table::_( 'platforms' )->get( $plugin->platform );
			$settings = $instance->getSettings();
			// Fill the form.
			$plans = Table::_( 'plans' )->getByPlugin( $plugin->id );
			$form = new Admin_Form_Statistics_InstanceSettings();
			$form->setPlans( $plans );
			// Process submitted data.
			if ( $this->_request->isPost() ) {
				if ( $form->isValid( $this->_request->getPost() ) ) {
					foreach ( $form->getValues() as $key => $value ) {
						if ( $value == $settings->$key ) continue;
						$key = str_replace( '_', ' ', $key );
						$instance->setSetting( $key, $value );
						// Additional processing for trial period setting.
						if ( $key == 'trial period' ) {
							$instance->paidTill(
								( $value - $settings->trial_period ) * SECONDS_PER_DAY
							);
							if ( $platform->payment == 'outer' ) {
								switch ( $platform->name ) {
									// Shopify payment.
									case 'shopify':
										$instance->setSetting( 'trial changed', '1' );
										break;
								}
							}
						}
					}
					$this->view->message = Zend_Registry::get( 'translate' )->_( 'saved' );
				}
			}
			else {
				$form->populate( array (
					'trial_period' => $settings->trial_period,
					'current_plan' => $settings->current_plan,
					'exclude_from_stat' => $settings->exclude_from_stat,
				) );
			}
			// Prepare view.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->form = $form;
		}

		/**
		 * Installation statistics.
		 */
		public function installsAction()
		{
			// Load stat data.
			$installed = Table::_( 'instances' )->installStat( 'installed' )->toArray();
			$uninstalled = Table::_( 'instances' )->installStat( 'uninstalled' )->toArray();
			$this->_installsChartData( $installed, $uninstalled );
			// Prepare view.
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'install statistics' );
			$this->view->headTitle( $this->view->title );
			$this->view->formFilter = new Admin_Form_Statistics_InstallFilter();
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
		}

		/**
		 * Forms installations chart data.
		 * It's supposed at least 2 row to be in an array.
		 *
		 * @param array $installedRows - installation data.
		 * @param array $uninstalledRows - uninstallation data.
		 */
		private function _installsChartData( $installedRows, $uninstalledRows )
		{
			// Prepare installed stat data. TODO end + reset
			$first = array_shift( $installedRows );
			array_unshift( $installedRows, $first );
			$last = array_pop( $installedRows );
			array_push( $installedRows, $last );
			$installed = array ();
			foreach ( $installedRows as $row ) {
				$installed[ $row['period'] ] = $row;
			}
			// Prepare uninstalled stat data.
			$uninstalled = array ();
			foreach ( $uninstalledRows as $row ) {
				$uninstalled[ $row['period'] ] = $row;
			}
			// Set range borders.
			$firstDate = new DateTime( $first['period'] );
			$lastDate = new DateTime( $last['period'] );
			$lastDateCopy = clone $lastDate;
			$diff = $lastDate->diff( $firstDate );
			$this->view->startRangeDate = $firstDate->format( 'Y, m, d' );
			$this->view->endRangeDate = $lastDate->format( 'Y, m, d, 23:59:59' );
			if ( $diff->days > 30 ) {
				$lastDateCopy->modify( '-1 month' );
				$this->view->startRangeDate = $lastDateCopy->format( 'Y, m, d' );
			}
			// Form chart data by days.
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_date', 'label' => 'Date', 'type' => 'date' ),
					array ( 'id' => 'col_installed', 'label' => 'Installed', 'type' => 'number' ),
					array ( 'id' => 'col_uninstalled', 'label' => 'Uninstalled', 'type' => 'number' )
				),
				'rows' => array ()
			);
			while ( $diff->days >= 0 ) {
				$format = $firstDate->format( 'Y-m-d' );
				$zf_format = new Zend_Json_Expr( 'new Date(\''. $format .'\')' );
				$_installed = isset ( $installed[ $format ] ) ? $installed[ $format ]['amount'] : 0;
				$_uninstalled = isset ( $uninstalled[ $format ] ) ? $uninstalled[ $format ]['amount'] : 0;
				$chartData['rows'][] = array (
					'c' => array (
						array ( 'v' => $zf_format ), array ( 'v' => $_installed ), array ( 'v' => $_uninstalled )
					)
				);
				if ( $diff->days == 0 ) break;
				$firstDate->modify( '+1 day' );
				$diff = $lastDate->diff( $firstDate );
			}
			$this->view->chartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * Filtered installation statistics.
		 * @internal AJAX action.
		 */
		public function installsFilterAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'installs-filter', 'json' )
				->initContext( 'json' )
				;
			// Platform.
			$platform = Table::_( 'platforms' )->get(
				$this->_getParam( 'platform' )
			);
			$platformName = $platform ? $platform->name : null;
			// Plugin.
			$plugin = Table::_( 'plugins' )->get(
				$this->_getParam( 'plugin' )
			);
			$pluginId = $plugin ? $plugin->id : null;
			// Load stat data.
			$installed = Table::_( 'instances' )->installStat( 'installed', $platformName, $pluginId )->toArray();
			$uninstalled = Table::_( 'instances' )->installStat( 'uninstalled', $platformName, $pluginId )->toArray();
			$this->_installsChartData( $installed, $uninstalled );
		}

		/**
		 * Displays transactions information.
		 */
		public function transactionsAction()
		{
			$config = Config::getInstance();
			// Prepare filter params.
			$formFilter = new Admin_Form_Statistics_TransactionFilter();
			$filterParams = array ();
			if ( $this->_request->isPost() ) {
				if ( $formFilter->isValid( $this->_request->getPost() ) ) {
					$filterParams = $formFilter->getValues();
					$this->view->filtered = true;
				}
			}
			// Load history.
			$page = $this->_getParam( 'page', 1 );
			$history = Model::_( 'payment' )->combinedHistory( $filterParams );
			$paginator = Zend_Paginator::factory( $history );
			$paginator->setItemCountPerPage(
				$config->plugin->center->admin->itemsPerPage
			);
			$paginator->setCurrentPageNumber( $page );
			// Prepare view.
			$this->view->paginator = $paginator;
			$this->view->formFilter = $formFilter;
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'payment history' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Displays charges information.
		 */
		public function chargesAction()
		{
			$config = Config::getInstance();
			// Prepare filter params.
			$formFilter = new Admin_Form_Statistics_ChargeFilter();
			$filterParams = array ();
			if ( $this->_request->isPost() ) {
				if ( $formFilter->isValid( $this->_request->getPost() ) ) {
					$filterParams = $formFilter->getValues();
					$this->view->filtered = true;
				}
			}
			// Load history.
			$page = $this->_getParam( 'page', 1 );
			$history = Table::_( 'charges' )->getAdminList( $filterParams );
			$paginator = Zend_Paginator::factory( $history );
			$paginator->setItemCountPerPage(
					$config->plugin->center->admin->itemsPerPage
			);
			$paginator->setCurrentPageNumber( $page );
			// Prepare view.
			$this->view->paginator = $paginator;
			$this->view->formFilter = $formFilter;
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'payment history' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * webhooks stats
		 * @author Kuksanau Ihnat
		 */
		public function webhooksAction()
		{
			// Load stat data.
			$webhooks = Table::_( 'webhooksStats' )->getStats()->toArray();
			$this->_itemsChartData( $webhooks, 'webhooks' );
			// Prepare view.
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'webhooks statistics' );
			$this->view->headTitle( $this->view->title );
			$this->view->formFilter = new Admin_Form_Statistics_WebhooksFilter();
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
		}

		/**
		 * Filtered webhooks statistics.
		 * @internal AJAX action.
		 * @author Kuksanau Ihnat
		 */
		public function webhooksFilterAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'webhooks-filter', 'json' )
				->initContext( 'json' )
				;
			// Load stat data.
			$webhooks = Table::_( 'webhooksStats' )
				->getStats( $this->_getParam('platform',0), $this->_getParam('plugin',0) )
				->toArray();
			$this->_itemsChartData( $webhooks, 'webhooks' );
		}

		/**
		 * @todo refactoring
		 * prepare google charts data for webhooks
		 * @param $webhooks
		 * @author Kuksanau Ihnat
		 */
		protected function _itemsChartData( $items, $title )
		{
			// Prepare installed stat data.
			$first = reset( $items );
			$last = end( $items );
			$_items = array ();
			foreach ( $items as $row ) {
				$_items[ $row['period'] ] = $row;
			}
			// Set range borders.
			$firstDate = new DateTime( $first['period'] );
			$lastDate = new DateTime( $last['period'] );
			$lastDateCopy = clone $lastDate;
			$diff = $lastDate->diff( $firstDate );
			$this->view->startRangeDate = $firstDate->format( 'Y, m, d' );
			$this->view->endRangeDate = $lastDate->format( 'Y, m, d, 23:59:59' );
			if ( $diff->days > 30 ) {
				$lastDateCopy->modify( '-1 month' );
				$this->view->startRangeDate = $lastDateCopy->format( 'Y, m, d' );
			}
			// prepare chart data
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_date', 'label' => 'Date', 'type' => 'date' ),
					array ( 'id' => 'col_items', 'label' => ucfirst($title), 'type' => 'number' ),
				),
				'rows' => array ()
			);
			// fill chart data array day to day
			for( $i=0; $i <= $diff->days; $i++ ){
				$format = $firstDate->format( 'Y-m-d' );
				$zf_format = new Zend_Json_Expr( 'new Date(\''. $format .'\')' );
				$_data = isset ( $_items[ $format ] ) ? $_items[ $format ]['amount'] : 0;
				$chartData['rows'][] = array (
					'c' => array (
						array ( 'v' => $zf_format ),
						array ( 'v' => $_data )
					)
				);
				$firstDate->modify( '+1 day' );
			}
			$this->view->chartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * webhooks stats
		 * @author Kuksanau Ihnat
		 */
		public function emailsAction()
		{
			// Load stat data.
			$emails = Table::_( 'emails' )->getStats()->toArray();
			$this->_itemsChartData( $emails, 'emails' );
			// Prepare view.
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'emails statistics' );
			$this->view->headTitle( $this->view->title );
			$this->view->formFilter = new Admin_Form_Statistics_EmailsFilter();
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
		}

		/**
		 * Filtered webhooks statistics.
		 * @internal AJAX action.
		 * @author Kuksanau Ihnat
		 */
		public function emailsFilterAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'emails-filter', 'json' )
				->initContext( 'json' )
			;
			// Load stat data.
			$emails = Table::_( 'emails' )
				->getStats( $this->_getParam('platform',null), $this->_getParam('plugin',0) )
				->toArray();
			$this->_itemsChartData( $emails, 'emails' );
		}
	}
