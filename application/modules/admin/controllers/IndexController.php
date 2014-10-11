<?php
	/**
	 * Plugin Center dashboard.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.1.4
	 */
	class Admin_IndexController extends D_Admin_Controller_Abstract
	{
		/**
		 * Dashboard.
		 */
		public function indexAction()
		{
			$this->_installStat();
			$this->_platformInstallStat();
			$this->_pluginInstallStat();
			$this->_platformEarnedStat();
			$this->_pluginEarnedStat();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'dashboard' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Installation statistics for defined periods.
		 */
		private function _installStat()
		{
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'install-stat', 'json' )
				->initContext( 'json' )
				;
			// Prepare periods.
			$firstInstallDate = Table::_( 'instances' )->getFirstInstallDate();
			$firstInstallDate = new DateTime( $firstInstallDate );
			$periods = array (
				'all' => $this->_calcPeriodDays( $firstInstallDate ),
				'1y' => 365,
				'6m' => 180,
				'3m' => 90,
				'30d' => 30,
				'7d' => 7,
				'now' => 1
			);
			// Load data for each period.
			$data = array ();
			$prevPeriodData = array ();
			foreach ( $periods as $period => $days ) {
				// Installed.
				$installed = Table::_( 'instances' )->installStatForPeriod( 'installed', $period );
				$amount = $installed ? $installed->amount : 0;
				$averagePerDay = round( $amount / $days, 1 );
				$difference = 0;
				if ( isset ( $prevPeriodData['installed']['averagePerDay'] ) ) {
					$difference = $this->_calcDifference( $averagePerDay, $prevPeriodData['installed']['averagePerDay'] );
				}
				$data[ $period ]['installed'] = array (
					'amount' => $amount,
					'averagePerDay' => ( $period == 'now' ) ? null : $averagePerDay,
					'difference' => $difference
				);
				$prevPeriodData['installed']['averagePerDay'] = $averagePerDay;
				// Uninstalled.
				$uninstalled = Table::_( 'instances' )->installStatForPeriod( 'uninstalled', $period );
				$amount = $uninstalled ? $uninstalled->amount : 0;
				$averagePerDay = round( $amount / $days, 1 );
				$difference = 0;
				if ( isset ( $prevPeriodData['uninstalled']['averagePerDay'] ) ) {
					$difference = $this->_calcDifference( $averagePerDay, $prevPeriodData['uninstalled']['averagePerDay'] );
				}
				$data[ $period ]['uninstalled'] = array (
					'amount' => $amount,
					'averagePerDay' => ( $period == 'now' ) ? null : $averagePerDay,
					'difference' => $difference
				);
				$prevPeriodData['uninstalled']['averagePerDay'] = $averagePerDay;
				// Conversion.
				$value = 0;
				if ( $data[ $period ]['installed']['amount'] ) {
					$value = (
						  ( $data[ $period ]['installed']['amount'] * 100 )
						/ ( $data[ $period ]['installed']['amount'] + $data[ $period ]['uninstalled']['amount'] )
					);
					$value = round( $value, 1 );
				}
				$difference = null;
				if ( isset ( $prevPeriodData['conversion']['value'] ) ) {
					$difference = round( $value - $prevPeriodData['conversion']['value'], 1 );
				}
				$data[ $period ]['conversion'] = array (
					'value' => $value,
					'difference' => $difference
				);
				$prevPeriodData['conversion']['value'] = $value;
			}
			// Calculate difference between 'all' and '7d'.
			$data['all']['installed']['difference'] = $this->_calcDifference(
				$data['all']['installed']['averagePerDay'], $data['7d']['installed']['averagePerDay']
			);
			$data['all']['uninstalled']['difference'] = $this->_calcDifference(
				$data['all']['uninstalled']['averagePerDay'], $data['7d']['uninstalled']['averagePerDay']
			);
			$data['all']['conversion']['difference'] = round(
				$data['all']['conversion']['value'] - $data['7d']['conversion']['value'], 1
			);
			// Prepare view.
			$this->view->periods = array_reverse( $data, true );
		}

		/**
		 * Installation statistics by platforms.
		 * Google chart.
		 */
		private function _platformInstallStat()
		{
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_platforms', 'label' => 'Platforms', 'type' => 'string' ),
					array ( 'id' => 'col_amount', 'label' => 'Amount', 'type' => 'number' ),
				),
				'rows' => array ()
			);
			$installed = Table::_( 'instances' )->installStatForPeriod( 'installed', 'all', true );
			if ( count( $installed ) ) {
				foreach ( $installed as $platform ) {
					$chartData['rows'][] = array (
						'c' => array (
							array ( 'v' => ucfirst( $platform->platform ) ),
							array ( 'v' => (integer) $platform->amount )
						)
					);
				}
			}
			$this->view->platformChartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * Installation statistics by plugins.
		 * Google chart.
		 */
		private function _pluginInstallStat()
		{
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_app', 'label' => 'Application', 'type' => 'string' ),
					array ( 'id' => 'col_amount', 'label' => 'Amount', 'type' => 'number' ),
				),
				'rows' => array ()
			);
			$installed = Table::_( 'instances' )->installStatForPeriod( 'installed', 'all', false, true );
			if ( count( $installed ) ) {
				foreach ( $installed as $plugin ) {
					$chartData['rows'][] = array (
						'c' => array (
							array ( 'v' => $plugin->plugin_name .' ['. ucfirst( $plugin->platform ) .']' ),
							array ( 'v' => (integer) $plugin->amount )
						)
					);
				}
			}
			$this->view->pluginChartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * Earned statistics by platform.
		 * Google chart.
		 */
		private function _platformEarnedStat()
		{
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_app', 'label' => 'Platform', 'type' => 'string' ),
					array ( 'id' => 'col_amount', 'label' => 'Amount', 'type' => 'number' ),
				),
				'rows' => array ()
			);
			$platforms = array ();
			// Add transaction stat.
			$transactions = Table::_( 'transactions' )->totalPlatformEarned();
			if ( count( $transactions ) ) {
				foreach ( $transactions as $tr ) {
					$platforms[ $tr->id ] = array (
						'title' => $tr->title,
						'totalEarned' => ( isset ( $tr->totalEarned ) ? (float) $tr->totalEarned : 0 )
					);
				}
			}
			// Add charge stat.
			$charges = Table::_( 'charges' )->totalPlatformEarned();
			if ( count( $charges ) ) {
				foreach ( $charges as $ch ) {
					$totalEarned = ( isset ( $ch->totalEarned ) ? (float) $ch->totalEarned : 0 );
					if ( isset ( $platforms[ $ch->id ] ) ) {
						$platforms[ $ch->id ]['totalEarned'] += $totalEarned;
					} else {
						$platforms[ $ch->id ] = array (
							'title' => $ch->title,
							'totalEarned' => $totalEarned
						);
					}
				}
			}
			// Form chart data.
			if ( count( $platforms ) ) {
				foreach ( $platforms as $platform ) {
					$chartData['rows'][] = array (
						'c' => array (
							array ( 'v' => $platform['title'] ),
							array ( 'v' => $platform['totalEarned'] )
						)
					);
				}
			}
			$this->view->platformEarnedChartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * Earned statistics by plugin.
		 * Google chart.
		 */
		private function _pluginEarnedStat()
		{
			$chartData = array (
				'cols' => array (
					array ( 'id' => 'col_app', 'label' => 'Application', 'type' => 'string' ),
					array ( 'id' => 'col_amount', 'label' => 'Amount', 'type' => 'number' ),
				),
				'rows' => array ()
			);
			$plugins = array ();
			// Add transaction stat.
			$transactions = Table::_( 'transactions' )->totalPluginEarned();
			if ( count( $transactions ) ) {
				foreach ( $transactions as $tr ) {
					$plugins[ $tr->plugin_id ] = array (
						'title' => $tr->name .' ['. ucfirst( $tr->platform ) .']',
						'totalEarned' => ( isset ( $tr->totalEarned ) ? (float) $tr->totalEarned : 0 )
					);
				}
			}
			// Add charge stat.
			$charges = Table::_( 'charges' )->totalPluginEarned();
			if ( count( $charges ) ) {
				foreach ( $charges as $ch ) {
					$totalEarned = ( isset ( $ch->totalEarned ) ? (float) $ch->totalEarned : 0 );
					if ( isset ( $plugins[ $ch->plugin_id ] ) ) {
						$plugins[ $ch->plugin_id ]['totalEarned'] += $totalEarned;
					} else {
						$plugins[ $ch->plugin_id ] = array (
							'title' => $ch->name .' ['. ucfirst( $ch->platform ) .']',
							'totalEarned' => $totalEarned
						);
					}
				}
			}
			// Form chart data.
			if ( count( $plugins ) ) {
				foreach ( $plugins as $plugin ) {
					$chartData['rows'][] = array (
						'c' => array (
							array ( 'v' => $plugin['title'] ),
							array ( 'v' => $plugin['totalEarned'] )
						)
					);
				}
			}
			$this->view->pluginEarnedChartData = Zend_Json::encode( $chartData, false, array (
				'enableJsonExprFinder' => true
			) );
		}

		/**
		 * Calculates day amount of given period.
		 * @param string | DateTime $period - formatted period.
		 * @return integer
		 */
		private function _calcPeriodDays( $period )
		{
			if ( is_string( $period ) ) {
				$startDate = new DateTime();
				$startDate->modify( $period );
				$startDate->setTime( 0, 0, 0 );
			} else {
				$startDate = $period;
			}
			$endDate = new DateTime();
			$endDate->setTime( 0, 0, 0 );
			$interval = $endDate->diff( $startDate );
			return $interval->days;
		}

		/**
		 * Calculates difference between numbers.
		 * @param float $current - current amount.
		 * @param float $previous - previous amount.
		 * @return integer | float
		 */
		private function _calcDifference( $current, $previous )
		{
			if ( $previous && $current ) {
				$percent = ( $current / $previous ) * 100;
				$difference = round( $percent - 100, 1 );
			} else if ( !$previous && $current ) {
				$difference = 100;
			} else if ( !$current && $previous ) {
				$difference = -100;
			} else {
				$difference = 0;
			}
			return $difference;
		}

		/**
		 * Makes an admin Logged in.
		 */
		public function loginAction()
		{
			Zend_Layout::getMvcInstance()->setLayout( 'admin-login' );
			$config = Config::getInstance();
			// Process a form request.
			if ( $this->getRequest()->isPost() ) {
				// Get an admin.
				$admin = Table::_( 'admins' )->getAdmin(
					$this->_getParam( 'nickname' )
				);
				if ( $admin ) {
					// Check password.
					$password = trim( mcrypt_decrypt( MCRYPT_DES, $config->crypt->key, $admin->password, MCRYPT_MODE_ECB ) );
					if ( $password == $this->_getParam( 'password' ) ) {
						// Log in and go to dashboard.
						$session = new Zend_Session_Namespace( 'pc.admin' );
						$session->admin = $admin;
						return $this->getHelper( 'Redirector' )
							->gotoSimple( 'index' );
					} else {
						$this->view->password = false;
					}
				} else {
					$this->view->nickname = false;
				}
			}
		}

		/**
		 * Makes an admin Logged out.
		 */
		public function logoutAction()
		{
			$session = new Zend_Session_Namespace( 'pc.admin' );
			$session->admin = null;
			return $this->getHelper( 'Redirector' )
				->gotoSimple( 'login' );
		}
	}
