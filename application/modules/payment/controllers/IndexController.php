<?php
	/**
	 * Gives possibility to interact with 2Checkout and Shopify payment systems,
	 * and to keep information about transactions for shopify plugins.
	 * 2Checkout payment system {@link http://www.2checkout.com/}.
	 * Shopify payment system {@link http://docs.shopify.com/api/tutorials/shopify-billing-api}.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 2.2.7
	 */
	class Payment_IndexController extends Zend_Controller_Action
	{
		/**
		 * Full information about plugin payment state.
		 * Gives final html for plugin payment tab.
		 *
		 * @internal CLI action.
		 * @internal [ platform ] [ shop ] [ plugin ]
		 */
		public function detailsAction()
		{
			$config = Config::getInstance();
			// Platform.
			$platform = Table::_( 'platforms' )->get(
				$this->_getParam( 'platform' )
			);
			$this->view->platform = $platform->name;
			// Shop.
			$shop = $this->view->shop = Table::_( 'shops' )->getForUser(
				$this->_getParam( 'user' )
			);
			// Plugin.
			$plugin = $this->view->plugin = Model::_( 'payment' )->getPlugin(
				$platform->name, $this->_getParam( 'plugin' )
			);
			$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
			$settings = $this->view->settings = $instance->getSettings();
			$plans = $this->view->plans = Model::_( 'payment' )->plans( $plugin->id, $settings->current_plan );
			$currentPlan = $this->view->currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $settings->current_plan );
			// Options.
			$this->view->currentPlanOptions = Table::_( 'options' )->getForPlan( $currentPlan->id );
			$this->view->currentPlanOptionsText = '';
			if ( count( $this->view->currentPlanOptions ) ) {
				foreach ( $this->view->currentPlanOptions as $_option ) {
					$this->view->currentPlanOptionsText .= '<p>'. $_option->name .': '. $_option->value .' '. $_option->unit .'</p>';
				}
			}
			// Option statistics.
			$this->view->previousMonthStatistics = Table::_( 'optionStat' )->previous( $shop->id, $plugin->id, $currentPlan->id );
			$this->view->currentMonthStatistics = Table::_( 'optionStat' )->current( $shop->id, $plugin->id, $currentPlan->id );
			$this->view->overdrafts = $this->getHelper( 'Options' )->overdrafts( $shop->id, $plugin->id );
			$this->view->overdraftTotalPrice = $this->getHelper( 'Options' )->overdraftTotalPrice();
			// If a plan was changed.
			if ( $settings->plan_changed ) {
				Model::_( 'payment' )->setting( $shop, $plugin, 'plan changed', 0 );
				$this->view->planChanged = 1;
				$this->view->oldRest = round( $settings->old_rest / SECONDS_PER_DAY );
				$this->view->newRest = round( $settings->new_rest / SECONDS_PER_DAY );
				$this->view->direction = ( $this->view->oldRest < $this->view->newRest ? 'increased' : 'decreased' );
				$this->view->termsUrl = $config->plugin->center->baseUrl . "default/index/page/{$platform->name}/terms";
			}
			// Trial period.
			if ( $settings->trial_period ) {
				$this->view->paidTill = $instance->paidTill();
				$this->view->formattedPaidTill = date( "j M Y", $this->view->paidTill );
			}
			// Inner payment system.
			if ( $platform->payment == 'inner' ) {
				$prefix = '';
				// Transaction info.
				$transaction = Model::_( 'payment' )->getLastTransaction( $shop->id, $plugin->id );
				if ( $transaction ) {
					$this->getHelper( '2co' )->setTransaction( $transaction );
					$this->view->transaction = $transaction;
					$this->view->history = Model::_( 'payment' )->history( $shop, $plugin );
					$this->view->recurrence = $transaction->recurring;
					$this->view->cancelRecurrentUrl = $config->plugin->center->baseUrl .'payment/index/cancel-recurrent';
					$this->view->paidTill = $instance->paidTill();
					$this->view->formattedPaidTill = date( "j M Y", $this->view->paidTill );
					$this->view->lastPayDate = $this->getHelper( '2co' )->transactionDate();
					// Standard transaction.
					if ( $this->getHelper( '2co' )->transactionDetail( 'total' ) ) {
						$this->view->lastPayAmount = $this->getHelper( '2co' )->transactionDetail( 'total' )
							.' ( for '. $this->getHelper( '2co' )->transactionDetail( 'name' ) .' )';
					}
					// Recurrent installment.
					else {
						$this->view->lastPayAmount = $this->getHelper( '2co' )->transactionDetail( 'item_usd_amount_1' )
							.' ( for '. $this->getHelper( '2co' )->transactionDetail( 'item_name_1' ) .' )';
					}
					// Overdrafts.
					$this->view->overdraftUnpaidPeriod = Table::_( 'optionStat' )->overdraftUnpaidPeriod( $shop->id, $plugin->id );
					$matches = array ();
					if ( preg_match_all( '/li_[1-9]_price=(\d+)/', $transaction->details, $matches ) ) {
						$this->view->lastPayOverdraftAmount = 0;
						foreach ( $matches[1] as $_price ) {
							$this->view->lastPayOverdraftAmount += $_price;
						}
					}
				}
			}
			// Outer payment system.
			else {
				$prefix = $platform->name . '_';
				$tplName = $platform->name . '-details';
				$this->_helper->viewRenderer->setRender( $tplName );
				switch ( $platform->name ) {
					// Shopify.
					case 'shopify':
						// Charge info.
						$charge = Model::_( 'payment' )->getLastCharge( $shop->id, $plugin->id );
						if ( $charge ) {
							$this->view->charge = $charge;
							$this->view->history = Model::_( 'payment' )->getChargeHistory( $charge->charge_id );
							$this->view->cancelRecurrentUrl = $config->plugin->center->baseUrl . 'payment/shopify/cancel-recurring-charge';
							$this->view->paidTill = $instance->paidTill();
							$this->view->formattedPaidTill = date( "j M Y", $this->view->paidTill );
							$this->view->lastPayDate = date( "j M Y", strtotime( $charge->date ) );
							$this->view->lastPayAmount = '$'. $charge->amount .' ( for '. $charge->detail( 'name' ) .' )';
							// Overdrafts.
							$this->view->overdraftUnpaidPeriod = Table::_( 'optionStat' )->overdraftUnpaidPeriod( $shop->id, $plugin->id );
							$this->view->lastPayOverdraftAmount = ( $charge->overdraft == 1 ) ? $charge->amount : 0;
						}
						break;
				}
			}
			//	View.
			$this->view->instanceId = $instance->id;
			$this->view->host = $config->placeholders->host;
			$this->view->pcBaseURL = $config->plugin->center->baseUrl;
			// CSS, JS.
			$this->view->css = file_get_contents( realpath( APPLICATION_PATH.'/../public/css/common.css' ) );
			$this->view->css .= "\n\n". file_get_contents( realpath( APPLICATION_PATH.'/../public/css/payment.css' ) );
			$this->view->css = str_replace( '{imageUrl}', $config->plugin->center->baseUrl .'public/images/', $this->view->css );
			$this->view->css .= "\n\n". file_get_contents( realpath( APPLICATION_PATH.'/../public/css/tipsy.css' ) );
			$this->view->iecss = file_get_contents( realpath( APPLICATION_PATH.'/../public/css/common_gteie7.css' ) );
			$this->view->iecss .= "\n\n". file_get_contents( realpath( APPLICATION_PATH.'/../public/css/payment_gteie7.css' ) );
			$this->view->iecss = str_replace( '{imageUrl}', $config->plugin->center->baseUrl .'public/images/', $this->view->iecss );
			$this->view->js = file_get_contents( realpath( APPLICATION_PATH.'/../public/js/jquery.tipsy.js' ) );
			$this->view->js .= "\n\n". file_get_contents( realpath( APPLICATION_PATH .'/../public/js/'. $prefix .'payment.js' ) );
			$this->view->js .= "\n\n". file_get_contents( realpath( APPLICATION_PATH .'/../public/js/details.js' ) );
			$this->view->js = str_replace( '{pluginName}', $plugin->name, $this->view->js );
		}

		/**
		 * Gets setting by name
		 * @internal CLI action.
		 * @internal [ instance ]
		 */
		public function getSettingAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Platform.
			$platform = strtolower( $this->_getParam( 'platform' ) );
			// Shop.
			$shop = Table::_( 'credentials' )->getUserShop(
				$this->_getParam( 'user' )
			);
			// Plugin.
			$plugin = Model::_( 'payment' )->getPlugin(
				$platform, $this->_getParam( 'plugin' )
			);
			$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
			echo $instance->getSetting( $this->_getParam( 'option' ) )->value;
		}
		
		/**
		 * Sets instance setting on payment info tab.
		 * @internal IFRAME action.
		 * @internal [ instance ]
		 */
		public function setSettingAction()
		{
			$this->getHelper( 'layout' )->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			// Instance.
			$instance = Table::_( 'instances' )->get(
				$this->_getParam( 'instance' )
			);
			// Validate an user.
			$tablePlugins = new Default_Model_DbTable_Plugins();
			$plugin = $tablePlugins->get( $instance->plugin_id );
			$user = $this->getHelper( $plugin->platform )
				->validateUser( $plugin->name );
			// Set setting.
			if ( $user ) {
				$instance->setSetting(
					$this->_getParam( 'name' ),
					$this->_getParam( 'value' )
				);
			}
		}

		/**
		 * Checks out whether usage is paid or not.
		 * @internal CLI action.
		 * @internal [ platform ] [ plugin ] [ user ]
		 */
		public function isPaidAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$modelPayment = new Payment_Model_Payment();
			// Platform.
			$platform = strtolower( $this->_getParam( 'platform' ) );
			// Shop and plan.
			$shop = Table::_( 'credentials' )->getUserShop(
				$this->_getParam( 'user' )
			);
			// Plugin.
			$plugin = $modelPayment->getPlugin(
				$platform, $this->_getParam( 'plugin' )
			);
			$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
			$settings = $instance->getSettings();
			// Plan.
			$plan = Table::_( 'plans' )->getPlan( $plugin->id, $settings->current_plan );
			// Shopify charge.
			$charge = null;
			if ( $platform == 'shopify' ) {
				$charge = Table::_( 'charges' )->fetchRow( array (
					'shop_id = ?' => $shop->id,
					'plugin_id = ?' => $plugin->id,
					'payment_plan_id = ?' => $plan->payment_plan_id,
					'status = ?' => 'active',
					'recurring = ?' => '1'
				) );
			}
			// Checking.
			$isPaid = (
					( $plan->isFree() || ( time() < $instance->paidTill() ) || ( ( $platform == 'shopify' ) && $charge ) )
				&& !Table::_( 'optionStat' )->overdraftUnpaidPeriod( $shop->id, $plugin->id )
			);
			if ( $isPaid ) {
				$isSuspended = (
						$settings->suspend_app
					&& Table::_( 'optionStat' )->hasCurrentMonthOverdraf( $instance->shop_id, $instance->plugin_id, $plan->id )
				);
				echo $isSuspended ? 'suspended' : 'paid';
				return;
			}
			echo 'stopped';
		}

		/**
		 * Returns user current payment plan.
		 * @internal CLI action.
		 * @internal [ plugin ] [ user ]
		 */
		public function getCurrentPlanAction()
		{
			$this->getHelper( 'contextSwitch' )
				->addActionContext( 'get-current-plan', 'json' )
				->initContext( 'json' )
				;
			// Get plan.
			$user = Table::_( 'users' )->get(
				$this->_getParam( 'user' )
			);
			$shop = Table::_( 'shops' )->get( $user->shop_id );
			$plugin = Model::_( 'payment' )->getPlugin(
				$user->platform, $this->_getParam( 'plugin' )
			);
			$this->view->currentPlan = Model::_( 'payment' )->setting( $shop, $plugin, 'current plan' );
		}
	}
