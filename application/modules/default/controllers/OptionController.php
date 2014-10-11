<?php
	/**
	 * Provides with means of statistics renewal about usage of option entity.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.0.8
	 */
	class Default_OptionController extends Zend_Controller_Action
	{
		/**
		 * Common method parameters.
		 * @var array
		 */
		private $_data;

		/**
		 * Allowed actions to call by plugin.
		 * @var array
		 */
		private $_allowedActions = array ( 'increase', 'decrease', 'set', 'set-max' );

		/**
		 * Initialization.
		 */
		public function init()
		{
			if ( in_array( $this->_request->getActionName(), $this->_allowedActions ) ) {
				$this->getHelper( 'ViewRenderer' )->setNoRender( true );
				$modelPayment = new Payment_Model_Payment();
				// Parameters.
				$platform = $this->_getParam( 'platform' );
				$shop = Table::_( 'shops' )->getForUser( $this->_getParam( 'user' ) );
				$plugin = $modelPayment->getPlugin( $platform, $this->_getParam( 'plugin' ) );
				$this->_data = array (
					'shop_id' => $shop->id,
					'plugin_id' => $plugin->id,
					'key' => $this->_getParam( 'option' ),
					'value' => (integer) $this->_getParam( 'value' )
				);
				// Exit if still trial.
				$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
				$basePlan = $this->getHelper( $plugin->platform )->getBasePlan( $currentPlan );
				$installationDate = Table::_( 'plugins' )->installationDate( $shop, $plugin );
				$trialPeriod = $modelPayment->setting( $shop, $plugin, 'trial period' );
				$plan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
				$trialPeriod = (
					   $trialPeriod && !$plan->isFree()
					&& ( strtotime( "{$installationDate} +{$trialPeriod} days" ) > time() )
				);
				if ( $trialPeriod ) {
					exit;
				}
			}
		}

		/**
		 * Returns an option by key.
		 * Respons in JSON format.
		 *
		 * @internal CLI action.
		 * @internal [ platform ] [ plugin ]
		 * @return string
		 */
		public function getOptionAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$modelPayment = new Payment_Model_Payment();
			// Parameters.
			$platform = $this->_getParam( 'platform' );
			$key = $this->_getParam( 'option' );
			$shop = Table::_( 'shops' )->getForUser( $this->_getParam( 'user' ) );
			$plugin = $modelPayment->getPlugin( $platform, $this->_getParam( 'plugin' ) );
			$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
			$currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
			// Option selecting.
			if ( $option = Table::_( 'options' )->getForStatistics( $currentPlan->id, $key ) ) {
				return json_encode( $option->toArray() );
			}
			return null;
		}

		/**
		 * Increases statistical value.
		 * @internal CLI action.
		 * @internal [ plugin ]
		 */
		public function increaseAction() {
			Table::_( 'optionStat' )->increase( $this->_data );
		}

		/**
		 * Decreases statistical value.
		 * @internal CLI action.
		 * @internal [ plugin ]
		 */
		public function decreaseAction() {
			Table::_( 'optionStat' )->decrease( $this->_data );
		}

		/**
		 * Sets statistical value.
		 * @internal CLI action.
		 * @internal [ plugin ]
		 */
		public function setAction() {
			Table::_( 'optionStat' )->set( $this->_data );
		}

		/**
		 * Sets statistical value only if it higher then current.
		 * @internal CLI action.
		 * @internal [ plugin ]
		 */
		public function setMaxAction() {
			Table::_( 'optionStat' )->setMax( $this->_data );
		}

		/**
		 * Saves current option values in a record of previous month statistics.
		 * @internal Cron action.
		 */
		public function fillPreviousMonthAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Start cron.
			$this->getHelper( 'admin' )
				->startCronTask( 'fill-previous-month' );
			// Prepare.
			$config = Config::getInstance();
			$modelPayment = new Payment_Model_Payment();
			// Select all records with last month period.
			$period = date( "Y-m-00", strtotime( "-1 month" ) );
			if ( $statistics = Table::_( 'optionStat' )->previousPeriod( $period ) ) {
				// Fill option's fields.
				foreach ( $statistics as $_record ) {
					$shop = Table::_( 'shops' )->get( $_record->shop_id );
					$plugin = Table::_( 'plugins' )->get( $_record->plugin_id );
					$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
					$currentPlan = Table::_( 'plans' )->getPlan( $_record->plugin_id, $currentPlan );
					if ( $option = Table::_( 'options' )->getForStatistics( $currentPlan->id, $_record->key ) ) {
						$currentValue = $_record->value / $option->overdraft_unit_count;
						$overdraftValue = $currentValue - $option->value;
						$price = $overdraftValue * $option->price_for_overdraft_unit;
						$overdraft =
						(
							( $currentValue > $option->value )
							&& ( $price > $config->options->overdraft->minimalPrice )
						);
						if ( $_record->use_for_payment ) {
							$_record->overdraft_status = $overdraft ? 'overdraft' : 'not overdraft';
						}
						$_record->option_name = $option->name;
						$_record->option_value = $option->value;
						$_record->option_unit = $option->unit;
						$_record->option_price_for_overdraft_unit = $option->price_for_overdraft_unit;
						$_record->option_overdraft_unit_count = $option->overdraft_unit_count;
						$_record->setReadOnly( false );
						$_record->save();
					}
				}
			}
			// Stop cron.
			$this->getHelper( 'admin' )
				->stopCronTask( 'fill-previous-month' );
		}

		/**
		 * Sends a notification about overdraft on current month.
		 * @internal Cron action.
		 */
		public function currentOverdraftNotificationAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$modelSettings = new Admin_Model_Settings();
			$modelPayment = new Payment_Model_Payment();
			// Check out all installed plugins.
			if ( $plugins = Table::_( 'plugins' )->getInstalledPlugins() ) {
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'option/notification/';
				foreach ( $plugins as $_instance ) {
					$shop = Table::_( 'shops' )->get( $_instance->shop_id );
					$plugin = Table::_( 'plugins' )->get( $_instance->plugin_id );
					$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
					$currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
					$statistics = Table::_( 'optionStat' )->current( $shop->id, $plugin->id, $currentPlan->id );
					// Skip if notification is disabled.
					$platform = Table::_( 'platforms' )->get( $plugin->platform );
					$enabled = $modelSettings->getPlatformValue( $platform->id, 'option overdraft', 'bool' );
					if ( !$enabled ) continue;
					// Process.
					if ( count( $statistics ) && $_instance->state == 'installed' ) {
						// Calculate overdrafts.
						$overdrafts = array ();
						foreach ( $statistics as $_record ) {
							if ( $_record->use_for_payment ) {
								$currentValue = $_record->os_value / $_record->overdraft_unit_count;
								$overdraftValue = $currentValue - $_record->value;
								$price = $overdraftValue * $_record->price_for_overdraft_unit;
								if ( $currentValue > $_record->value )  {
									$overdrafts[] = $_record->os_id;
								}
							}
						}
						// Sending process.
						if ( !empty ( $overdrafts ) && $shop->email ) {
							sleep( 1 );
							$currentOverdrafts = $overdrafts;
							$varName = 'options_overdraft_first_'. date( 'Ym' );
							if ( $sentOverdrafts = Table::_( 'variables' )->get( $varName, $_instance->id, true ) ) {
								$currentOverdrafts = array_diff( $overdrafts, $sentOverdrafts );
							}
							if ( !empty ( $currentOverdrafts ) ) {
								// Prepare body.
								$name = $helperEmail->formTemplateName( $plugin, $path, 'current_month_overdraft.phtml' );
								$body = $this->_replacePlaceholders(
									$shop, $plugin, $currentPlan->id, $currentPlan->name, $statistics,
									$this->view->render( "option/notification/{$name}" )
								);
								// Send email.
								$helperEmail->setSubject( $name )
									->getMailer()
									->clearRecipients()
									->addTo( $shop->email )
									->setBodyHtml( $body )
									->send()
									;
								// Update sending time.
								Table::_( 'variables' )->set( $varName, $overdrafts, $_instance->id, true );
								$notifications = Table::_( 'notifications' )->fetchRow( "`instance` = $_instance->id" );
								$notifications->options_overdraft_first = new Zend_Db_Expr( 'NOW()' );
								$notifications->save();
							}
						}
					}
				}
			}
		}

		/**
		 * Sends a notification about overdraft on previous month.
		 * @internal Cron action.
		 */
		public function previousOverdraftNotificationAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$modelSettings = new Admin_Model_Settings();
			$modelPayment = new Payment_Model_Payment();
			// Check out all installed plugins.
			if ( $plugins = Table::_( 'plugins' )->getInstalledPlugins() ) {
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'option/notification/';
				foreach ( $plugins as $_instance ) {
					$shop = Table::_( 'shops' )->get( $_instance->shop_id );
					$plugin = Table::_( 'plugins' )->get( $_instance->plugin_id );
					$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
					$currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
					$statistics = Table::_( 'optionStat' )->previous( $shop->id, $plugin->id, $currentPlan->id );
					// Skip if notification is disabled.
					$platform = Table::_( 'platforms' )->get( $plugin->platform );
					$enabled = $modelSettings->getPlatformValue( $platform->id, 'option overdraft', 'bool' );
					if ( !$enabled ) continue;
					// Process.
					if ( count( $statistics ) && $_instance->state == 'installed' ) {
						$formattedStatistics = array ();
						foreach ( $statistics as $_record ) {
							$formattedRecord = new stdClass();
							$formattedRecord->name = $_record->option_name;
							$formattedRecord->value = $_record->option_value;
							$formattedRecord->unit = $_record->option_unit;
							$formattedRecord->overdraft_unit_count = $_record->option_overdraft_unit_count;
							$formattedRecord->price_for_overdraft_unit = $_record->option_price_for_overdraft_unit;
							$formattedRecord->os_value = $_record->os_value;
							$formattedStatistics[] = $formattedRecord;
						}
						foreach ( $statistics as $_record ) {
							// Sending process.
							if ( ( $_record->overdraft_status == 'overdraft' ) && $shop->email ) {
								sleep( 1 );
								$notifications = Table::_( 'notifications' )->fetchRow( "`instance` = $_instance->id" );
								if ( $notifications ) {
									// Prepare body.
									$name = $helperEmail->formTemplateName( $plugin, $path, 'unpaid_overdraft.phtml' );
									$body = $this->_replacePlaceholders(
										$shop, $plugin, $currentPlan->id, $currentPlan->name, $formattedStatistics,
										$this->view->render( "option/notification/{$name}" )
									);
									// Send email.
									$helperEmail->setSubject( $name )
										->getMailer()
										->clearRecipients()
										->addTo( $shop->email )
										->setBodyHtml( $body )
										->send()
										;
									// Update sending time.
									$notifications->options_overdraft_remind = new Zend_Db_Expr( 'NOW()' );
									$notifications->save();
								}
								break;
							}
						}
					}
				}
			}
		}

		/**
		 * Sends a notification about any overdraft expiration.
		 * @internal Cron action.
		 */
		public function expiredOverdraftNotificationAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$modelSettings = new Admin_Model_Settings();
			$modelPayment = new Payment_Model_Payment();
			// Check out all installed plugins.
			if ( $plugins = Table::_( 'plugins' )->getInstalledPlugins() ) {
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'option/notification/';
				foreach ( $plugins as $_instance ) {
					$shop = Table::_( 'shops' )->get( $_instance->shop_id );
					$plugin = Table::_( 'plugins' )->get( $_instance->plugin_id );
					$currentPlan = $modelPayment->setting( $shop, $plugin, 'current plan' );
					$currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
					$statistics = Table::_( 'optionStat' )->expired( $shop->id, $plugin->id, $currentPlan->id );
					// Skip if notification is disabled.
					$platform = Table::_( 'platforms' )->get( $plugin->platform );
					$enabled = $modelSettings->getPlatformValue( $platform->id, 'option overdraft', 'bool' );
					if ( !$enabled ) continue;
					// Process.
					if ( count( $statistics ) && $_instance->state == 'installed' ) {
						$formattedStatistics = array ();
						foreach ( $statistics as $_record ) {
							$formattedRecord = new stdClass();
							$formattedRecord->name = $_record->option_name;
							$formattedRecord->value = $_record->option_value;
							$formattedRecord->unit = $_record->option_unit;
							$formattedRecord->overdraft_unit_count = $_record->option_overdraft_unit_count;
							$formattedRecord->price_for_overdraft_unit = $_record->option_price_for_overdraft_unit;
							$formattedRecord->os_value = $_record->os_value;
							$formattedStatistics[] = $formattedRecord;
						}
						foreach ( $statistics as $_record ) {
							// Sending process.
							if ( ( $_record->overdraft_status == 'overdraft' ) && $shop->email ) {
								sleep( 1 );
								$notifications = Table::_( 'notifications' )->fetchRow( "`instance` = $_instance->id" );
								if ( $notifications ) {
									// Prepare body.
									$name = $helperEmail->formTemplateName( $plugin, $path, 'expired_overdraft.phtml' );
									$body = $this->_replacePlaceholders(
										$shop, $plugin, $currentPlan->id, $currentPlan->name, $formattedStatistics,
										$this->view->render( "option/notification/{$name}" )
									);
									// Send email.
									$helperEmail->setSubject( $name )
										->getMailer()
										->clearRecipients()
										->addTo( $shop->email )
										->setBodyHtml( $body )
										->send()
										;
									// Update sending time.
									$notifications->options_overdraft_final = new Zend_Db_Expr( 'NOW()' );
									$notifications->save();
								}
								break;
							}
						}
					}
				}
			}
		}

		/**
		 * Replaces placeholders with values in email body.
		 * @param object $shop - shop.
		 * @param object $plugin - plugin.
		 * @param integer $currentPlanId - current plan id.
		 * @param string $currentPlan - current plan name.
		 * @param array $statistics - statistics.
		 * @param string $body - email body.
		 * @return string
		 */
		private function _replacePlaceholders( $shop, $plugin, $currentPlanId, $currentPlan, $statistics, $body )
		{
			$config = Config::getInstance();
			$translate = Zend_Registry::get( 'translate' );
			$modelPayment = new Payment_Model_Payment();
			// Prepare placeholders.
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
			$filter = new D_Filter_PluginDirectory();
			// Options.
			$currentPlanOptions = Table::_( 'options' )->getForPlan( $currentPlanId );
			$currentPlanOptionsText = '';
			if ( $currentPlanOptions ) {
				foreach ( $currentPlanOptions as $_option ) {
					$currentPlanOptionsText .= $_option->name .': '. $_option->value .' '. $_option->unit .'<br />';
				}
			}
			// Option statistics.
			$optionStatisticsText = '';
			$totalPrice = 0;
			foreach ( $statistics as $_optionStatistics ) {
				$optionStatisticsText .= '
					<strong>'. $_optionStatistics->name .'</strong><br />
					'. $translate->_( 'limit' ) .' : <strong>'. $_optionStatistics->value .' '. $_optionStatistics->unit .'</strong><br />
				';
				$currentValue = $_optionStatistics->os_value / $_optionStatistics->overdraft_unit_count;
				$currentValue = is_float( $currentValue ) ? round( $currentValue, 2 ) : $currentValue;
				// Overdraft.
				if ( $currentValue > $_optionStatistics->value ) {
					$overdraftValue = $currentValue - $_optionStatistics->value;
					$price = $overdraftValue * $_optionStatistics->price_for_overdraft_unit;
					$totalPrice += $price;
					// Current value.
					$optionStatisticsText .= $translate->_( 'current value' ) .' : <strong style="color: #F73333;"><blink>'. $currentValue .' '. $_optionStatistics->unit. '</blink></strong><br />';
					// Overdraft.
					$optionStatisticsText .= $translate->_( 'overdraft' ) .' : <strong>$'. $price .' ( '. $overdraftValue .' '. $_optionStatistics->unit .' * $'. $_optionStatistics->price_for_overdraft_unit .' )</strong><br />';
				}
				// No overdraft.
				else {
					// Current value.
					$optionStatisticsText .= $translate->_( 'current value' ) .' : <strong>'. $currentValue .' '. $_optionStatistics->unit. '</strong><br />';
				}
			}
			if ( $totalPrice ) {
				$optionStatisticsText .= '
					<strong><span>'. $translate->_( 'total amount for overdraft' ) .'</span> : $'. $totalPrice .'</strong><br />
				';
			}
			$placeholders = array (
				'username' => $user->name,
				'application_name' => $plugin->name,
				'link_to_app_login' => $config->plugin->{$plugin->platform}->baseUrl . $filter->filter( $plugin->name ),
				'client_tariff_plan' => $currentPlan,
				'tariff_plan_options' => $currentPlanOptionsText,
				'tariff_plan_stats' => $optionStatisticsText,
				'minimal_amount_to_pay' => $config->options->overdraft->minimalPrice,
				'contact_email' => $config->notification->email->fromAddress,
				'last_date_of_month' => date( 't' )
			);
			// Replace placeholders.
			foreach ( $placeholders as $_key => $_value ) {
				$body = str_replace( ':'.$_key, $_value, $body );
			}
			return $body;
		}
	}
