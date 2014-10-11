<?php
	/**
	 * Specific operations for Shopify payment system.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Payment
	 * @version 1.1.10
	 */
	class Payment_ShopifyController extends Zend_Controller_Action
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			$this->_helper->layout->disableLayout();
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
		}

		/**
		 * Recreates a charge after trial is changed.
		 * @todo user validation.
		 */
		public function recreateChargeAction()
		{
			$config = Config::getInstance();
			// Parameters.
			$this->_setParam( 'shopify_form', 'main' );
			// Cancel old charge.
			if ( !$this->_getParam( 'canceled' ) ) {
				$returnUrl = base64_encode( $this->view->url( array (
					'canceled' => '1'
				) ) );
				$this->_setParam( 'return_url', $returnUrl );
				$this->_forward( 'cancel-recurring-charge' );
			}
			// Create a new one.
			else {
				$chargeName = Zend_Registry::get( 'translate' )->_( 'pay for shopify app' );
				$returnUrl = $config->plugin->center->baseUrl . 'payment/shopify/finish-charge';
				$charge = Table::_( 'charges' )->get(
					$this->_getParam( 'charge_id' )
				);
				$shop = Table::_( 'shops' )->get( $charge->shop_id );
				$plugin = Table::_( 'plugins' )->get( $charge->plugin_id );
				$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
				$instance->setSetting( 'trial changed', '0' );
				$currentPlan = Table::_( 'plans' )->getPlan(
					$plugin->id, $instance->getSetting( 'current plan' )->value
				);
				$products = $currentPlan->getProducts();
				// Parameters.
				$this->_setParam( 'shop', base64_encode( $shop->name ) );
				$this->_setParam( 'plugin', base64_encode( $plugin->name ) );
				$this->_setParam( 'plan', base64_encode( $currentPlan->getName() ) );
				$this->_setParam( 'product_id', $products->getRow( 0 )->id );
				$this->_setParam( 'recurrent', '1' );
				$this->_setParam( 'recreate', '1' );
				$this->_setParam( 'charge_name', base64_encode( $chargeName ) );
				$this->_setParam( 'charge_price', $products->getRow( 0 )->price );
				$this->_setParam( 'charge_return_url', base64_encode( $returnUrl ) );
				// Forward.
				$this->_forward( 'do-charge' );
			}
		}

		/**
		 * Creates a charge and redirects a user to confirmation page.
		 * Before creating, authorization occurs.
		 */
		public function doChargeAction()
		{
			$config = Config::getInstance();
			// Save parameters in session.
			$session = new Zend_Session_Namespace( 'do-charge' );
			if ( $shopifyForm = $this->_getParam( 'shopify_form' ) ) {
				$session->shop = $this->_getParam( 'shop' );
				$session->plugin = $this->_getParam( 'plugin' );
				$session->plan = $this->_getParam( 'plan' );
				$session->charge->name = $this->_getParam( 'charge_name' );
				$session->charge->price = $this->_getParam( 'charge_price' );
				$session->charge->returnURL = $this->_getParam( 'charge_return_url' ); // TODO define it right here.
				$session->install = $this->_getParam( 'install' );
				$session->recreate = $this->_getParam( 'recreate' );
				// Standard payment set.
				if ( $shopifyForm == 'main' ) {
					$session->product_id = $this->_getParam( 'product_id' );
					$session->recurrent = (bool) $this->_getParam( 'recurrent' );
					$session->overdraft = 0;
				} else { // Overdraft set.
					$session->ids = explode( ',', $this->_getParam( 'ids' ) );
					$session->recurrent = 0;
					$session->overdraft = 1;
				}
				// Decode string parameters if a request is get.
				if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
					$session->shop = base64_decode( $session->shop );
					$session->plugin = base64_decode( $session->plugin );
					$session->plan = base64_decode( $session->plan );
					$session->charge->name = base64_decode( $session->charge->name );
					$session->charge->returnURL = base64_decode( $session->charge->returnURL );
				}
			}
			// Charge local data.
			$shop = Table::_( 'shops' )->get( $session->shop );
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
			$plugin = Model::_( 'payment' )->getPlugin( 'shopify', $session->plugin );
			$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
			$settings = $this->view->settings = $instance->getSettings();
			$token = Table::_( 'credentials' )->getForPlugin( $plugin->id, $user->id )->api_key;
			$session->token = $token;
			// Recurring or One-time charge.
			$chargeApiName = $session->recurrent ? 'recurring_application_charge' : 'application_charge';
			// Create a charge.
			$trialDays = 0;
			if ( $session->install ) {
				$trialDays = $session->trial_days = $settings->trial_period;
			} else if ( $shopifyForm == 'main' ) {
				$trialDays = $instance->getTrialRest( $settings->trial_period );
			}
			$response = $this->getHelper( 'ShopifyApi' )
				->initialize( $shop->name, $token )
				->post( $chargeApiName . 's', null, array (
					$chargeApiName => array (
						'name' => $session->charge->name,
						'price' => $session->charge->price,
						'return_url' => $session->charge->returnURL,
						'trial_days' => $trialDays,
						'test' => $config->payment->testmode
					)
				) );
			// Confirm charge.
			if ( isset ( $response->$chargeApiName ) ) {
				return $this->getHelper( 'Redirector' )->gotoUrl(
					$response->$chargeApiName->confirmation_url
				);
			}
		}

		/**
		 * Finishes charge creating.
		 * A user goes here after confirmation page.
		 */
		public function finishChargeAction()
		{
			// Parameters.
			$chargeId = $this->_getParam( 'charge_id' );
			// Charge local data.
			$session = new Zend_Session_Namespace( 'do-charge' );
			$shop = Table::_( 'shops' )->get( $session->shop );
			$plugin = Model::_( 'payment' )->getPlugin( 'shopify', $session->plugin );
			$instance = Table::_( 'plugins' )->getInstance( $shop->id, $plugin->id );
			$basePlan = $this->getHelper( 'Shopify' )
				->getBasePlan( $session->plan );
			$plan = Model::_( 'payment' )->getPlan( $basePlan );
			// Recurring or One-time charge.
			$chargeApiName = $session->recurrent ? 'recurring_application_charge' : 'application_charge';
			// Get shopify charge data.
			$api = $this->getHelper( 'ShopifyApi' );
			$api->initialize( $shop->name, $session->token );
			$response = $api->get( $chargeApiName . 's', $chargeId );
			// Activate charge.
			$chargeAccepted = (
				isset ( $response->$chargeApiName ) &&
				( $response->$chargeApiName->status == 'accepted' )
			);
			if ( $chargeAccepted ) {
				$charge = $response->$chargeApiName;
				$response = $api->post( $chargeApiName . 's/activate', $chargeId, array (
					$chargeApiName => (array) $charge
				) );
				// Save charge.
				if ( $response === true ) {
					$details = '';
					foreach ( $charge as $key => $val ) {
						$details .= "{$key}={$val}\n";
					}
					Table::_( 'charges' )->insert( array (
						'charge_id' => $chargeId,
						'shop_id' => $shop->id,
						'plugin_id' => $plugin->id,
						'payment_plan_id' => $plan->id,
						'product_id' => $session->overdraft ? null : $session->product_id,
						'status' => 'active',
						'recurring' => (integer) $session->recurrent,
						'overdraft' => (integer) $session->overdraft,
						'amount' => $charge->price,
						'date' => $charge->created_at,
						'details' => $details
					) );
					// Mark overdraft as paid.
					if ( $session->overdraft ) {
						$tableStatisticsOptions = new Default_Model_DbTable_OptionStatistics();
						foreach ( $session->ids as $id ) {
							$tableStatisticsOptions->overdraftStatus( $id, 'paid' );
						}
					}
/*
					// Change paid till date.
					else if () {
						$lastInsertId = Table::_( 'charges' )->getAdapter()->lastInsertId();
						$charge = Table::_( 'charges' )->get( $lastInsertId );
						$instance->paidTill(
							$charge->paidPeriod()
						);
					}
*/
				}
			}
			// Go to plugin.
			$action =  $session->install ? 'install' : '';
			$this->getHelper( 'Shopify' )
				->gotoPlugin( $plugin->name, $action );
		}

		/**
		 * Cancels last recurring charge.
		 */
		public function cancelRecurringChargeAction()
		{
			// Save parameters in session.
			$session = new Zend_Session_Namespace( 'cancel-recurring-charge' );
			if ( $this->_getParam( 'shopify_form' ) ) {
				$session->charge->id = $this->_getParam( 'charge_id' );
				if ( $returnUrl = $this->_getParam( 'return_url' ) ) {
					$session->returnUrl = $returnUrl;
				}
			}
			// Charge data.
			$charge = Table::_( 'charges' )->get( $session->charge->id );
			$shop = Table::_( 'shops' )->get( $charge->shop_id );
			$plugin = Table::_( 'plugins' )->get( $charge->plugin_id );
			$user = Table::_( 'users' )->getUserByShop( $shop->id );
			$token = Table::_( 'credentials' )->getForPlugin( $plugin->id, $user->id )->api_key;
			// Cancel recurrence.
			$response = $this->getHelper( 'ShopifyApi' )
				->initialize( $shop->name, $token )
				->delete( 'recurring_application_charges', $charge->charge_id )
				;
			// Update a charge.
			if ( $response === true ) {
				$charge->status = 'cancelled';
				$charge->save();
			}
			// Back to return url.
			if ( isset ( $session->returnUrl ) ) {
				$this->getHelper( 'Redirector' )
					->gotoUrl( base64_decode( $session->returnUrl ), array (
						'prependBase' => false
					) );
			} else { // Back to payment tab.
				$this->getHelper( 'Shopify' )
					->gotoPlugin( $plugin->name );
			}
		}

		/**
		 * Checks status of recurring charges.
		 * @internal Cron action.
		 */
		public function checkChargesAction()
		{
			// Start cron.
			$this->getHelper( 'admin' )
				->startCronTask( 'charge-checker' );
			// Process charges.
			$charges = Table::_( 'charges' )->fetchAll( array (
				'status = ?' => 'active'
			) );
			if ( count( $charges ) ) {
				foreach ( $charges as $charge ) {
					// Charge local data.
					$shop = Table::_( 'shops' )->get( $charge->shop_id );
					$user = Table::_( 'users' )->getUserByShop( $shop->id );
					$token = Table::_( 'credentials' )->getForPlugin( $charge->plugin_id, $user->id )->api_key;
					// Get shopify charge data.
					try {
						$apiName = 'recurring_application_charge';
						$response = $this->getHelper( 'ShopifyApi' )
							->initialize( $shop->name, $token )
							->get( $apiName . 's', $charge->charge_id )
							;
					} catch ( Exception $ex ) {
						continue;
					}
					if ( isset ( $response->$apiName ) ) {
						// Update status.
						if ( $charge->status != $response->$apiName->status ) {
							$charge->status = $response->$apiName->status;
							$charge->save();
						}
						// Insert bill if new.
						$billingDate = strtotime( $response->$apiName->billing_on );
						$bill = Table::_( 'chargesBills' )->hasBill( $response->$apiName );
						if ( ( $charge->status == 'active' ) && ( time() > $billingDate ) && !$bill ) {
							Table::_( 'chargesBills' )->bill( $response->$apiName );
							// Extend paid till date.
							$instance = Table::_( 'plugins' )->getInstance( $shop->id, $charge->plugin_id );
							$instance->paid_till = date( 'Y-m-d H:i:s', $billingDate + ( SECONDS_PER_DAY * 30 ) );
							$instance->save();
						}
					}
				}
			}
			// Stop cron.
			$this->getHelper( 'admin' )
				->stopCronTask( 'charge-checker' );
		}
	}
