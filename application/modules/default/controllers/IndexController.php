<?php
	/**
	 * Contains common cross-platform actions.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.3.9
	 */
	class Default_IndexController extends Zend_Controller_Action
	{
		public function indexAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			//$response = $this->getHelper( 'MCAPI' )->listSubscribe( '92e2a93726', 'carmelo_luis@rambler.ru' );
			//var_dump($response);
			//$response = $this->getHelper( 'MCAPI' )->listMembers( '92e2a93726' );
			//var_dump($response);
			//$response = $this->getHelper( 'MCAPI' )->campaigns();
			//var_dump($response);
			//$response = $this->getHelper( 'MCAPI' )->campaignSendNow( 'eacae28152' );
			//var_dump($response);
			//echo $this->getHelper( 'MCAPI' )->errorMessage;
		}

		/**
		 * A simple page.
		 * @internal HTTP action.
		 */
		public function pageAction()
		{
			$config = Config::getInstance();
			// Parameters.
			$platform = $this->_getParam( 'platform' );
			$page = $this->_getParam( 'page' );
			// View.
			$validator = new Zend_Validate_Alpha();
			if ( $validator->isValid( $platform ) && $validator->isValid( $page ) ) {
				$this->view->css = file_get_contents( realpath( APPLICATION_PATH.'/../public/css/common.css' ) );
				$this->view->css .= "\n\n". file_get_contents( realpath( APPLICATION_PATH."/../public/css/{$platform}.css" ) );
				$this->view->css = str_replace( '{imageUrl}', $config->plugin->center->baseUrl .'public/images/', $this->view->css );
				$this->view->iecss = file_get_contents( realpath( APPLICATION_PATH.'/../public/css/common_gteie7.css' ) );
				$this->view->iecss .= "\n\n". file_get_contents( realpath( APPLICATION_PATH."/../public/css/{$platform}_gteie7.css" ) );
				$this->view->iecss = str_replace( '{imageUrl}', $config->plugin->center->baseUrl .'public/images/', $this->view->iecss );
				$this->renderScript( "index/{$platform}_{$page}.phtml" );
			}
		}

		/**
		 * Email notification.
		 * @internal Cron action.
		 */
		public function notificationAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$config = Config::getInstance();
			// Check out all installed plugins.
			if ( $plugins = Table::_( 'plugins' )->getInstalledPlugins() ) {
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'index/notification/';
				foreach ( $plugins as $_plugin ) {
					$shop = Table::_( 'shops' )->get( $_plugin->shop_id );
					$plugin = Table::_( 'plugins' )->get( $_plugin->plugin_id );
					$firstDay = $config->notification->firstDay;
					$secondDay = $config->notification->secondDay;
					$expiredDay = $config->notification->expiredDay * -1;
					$period = intval( ( strtotime( $_plugin->paid_till ) - time() ) / SECONDS_PER_DAY );
					$trialPeriod = Model::_( 'payment' )->setting( $shop, $plugin, 'trial period' );
					// Skip if notification is disabled.
					$platform = Table::_( 'platforms' )->get( $plugin->platform );
					$enabled = Model::_( 'settings' )->getPlatformValue( $platform->id, 'run out expired', 'bool' );
					if ( !$enabled ) continue;
					// Skip if uninstalled.
					if ( $_plugin->state == 'uninstalled' ) continue;
					// Skip if free.
					$currentPlan = Model::_( 'payment' )->setting( $shop, $plugin, 'current plan' );
					$currentPlan = Table::_( 'plans' )->getPlan( $plugin->id, $currentPlan );
					if ( $currentPlan->isFree() ) continue;
					// Expressions.
					$isTrial = ( time() - strtotime( $_plugin->installation_date ) ) < ( $trialPeriod * SECONDS_PER_DAY ) ? true : false;
					$canSend =
					(
						(
								( $period <= $firstDay )
							&& ( $period > $secondDay )
							&& (
									( $_plugin->last_notification_1 == null )
									|| ( $_plugin->last_notification_period_1 > ( 24 * ( $firstDay - $secondDay ) ) )
								)
						)
						||
						(
								( $period <= $secondDay )
							&& ( $period > 0 )
							&& (
									( $_plugin->last_notification_2 == null )
									|| ( $_plugin->last_notification_period_2 > ( 24 * $secondDay ) )
								)
						)
					);
					$canSendExpired =
					(
						(
								( $period <= 0 )
							&& ( $period > $expiredDay )
							&& (
									( $_plugin->last_notification_e == null )
									|| ( $_plugin->last_notification_period_e > abs( ( 24 * $expiredDay ) ) )
								)
						)
					);
					// Base template name.
					if ( $canSend ) {
						$name = $isTrial ? 'trial_run_out.phtml' : 'run_out.phtml';
					} else if ( $canSendExpired ) {
						$name = $isTrial ? 'trial_expired.phtml' : 'expired.phtml';
					}
					// Sending process.
					if ( isset ( $name ) ) {
						// Prepare body.
						$name = $helperEmail->formTemplateName( $plugin, $path, $name );
						$body = $this->_replacePlaceholders(
							$this->view->render( "index/notification/{$name}" ),
							$_plugin, $period
						);
						// If email exists.
						if ( $shop->email ) {
							sleep( 1 );
							// Send email.
							$helperEmail->setSubject( $name )
								->getMailer()
								->clearRecipients()
								->addTo( $email )
								->setBodyHtml( $body )
								->send()
								;
							// Update sending time.
							$_plugin->setReadOnly( false );
							if ( ( $period <= $firstDay ) && ( $period > $secondDay ) ) {
								$_plugin->last_notification_1 = new Zend_Db_Expr( 'NOW()' );
							}
							else if ( ( $period <= $secondDay ) && ( $period > 0 ) ) {
								$_plugin->last_notification_2 = new Zend_Db_Expr( 'NOW()' );
							}
							else if ( ( $period <= 0 ) && ( $period > $expiredDay ) ) {
								$_plugin->last_notification_e = new Zend_Db_Expr( 'NOW()' );
							}
							$_plugin->save();
							unset ( $name );
						}
					}
				}
			}
		}

		/**
		 * Replaces placeholders in email body.
		 * @param string $body - body.
		 * @param object $plugin - instance.
		 * @param integer $period - number of days left.
		 * @return string
		 */
		private function _replacePlaceholders( $body, $plugin, $period )
		{
			$config = Config::getInstance();
			// Prepare placeholders.
			$user = Table::_( 'users' )->getUserByShop( $plugin->shop_id );
			$paidTill = $plugin->paid_till;
			$shop = Table::_( 'shops' )->get( $plugin->shop_id );
			$plugin = Table::_( 'plugins' )->get( $plugin->plugin_id );
			$currentPlan = Model::_( 'payment' )->setting( $shop, $plugin, 'current plan' );
			$basePlan = Model::_( 'payment' )->getPlan(
				$this->getHelper( $plugin->platform )->getBasePlan( $currentPlan )
			);
			$products = Model::_( 'payment' )->productsForPlan( $basePlan->id, $plugin->id );
			$filter = new D_Filter_PluginDirectory();
			$placeholders = array (
				'username' => $user->name,
				'application_name' => $plugin->name,
				'date_paid_till' => date( "j M Y", strtotime( $paidTill ) ),
				'days_left_to_expire' => $period,
				'link_to_app_login' => $config->plugin->{$plugin->platform}->baseUrl . $filter->filter( $plugin->name ),
				'client_tariff_plan' => $currentPlan,
				'one_month_payment_for_client_tariff_plan' => '$'. $products->getRow( 0 )->price,
				'three_months_payment_for_client_tariff_plan' => '$'. $products->getRow( 1 )->price
					.' ( Save: $'. ( ( $products->getRow( 0 )->price * $products->getRow( 1 )->quantity ) - $products->getRow( 1 )->price ) .' )',
				'six_months_payment_for_client_tariff_plan' => '$'. $products->getRow( 2 )->price
					.' ( Save: $'. ( ( $products->getRow( 0 )->price * $products->getRow( 2 )->quantity ) - $products->getRow( 2 )->price ) .' )',
				'twelve_months_payment_for_client_tariff_plan' => '$'. $products->getRow( 3 )->price
					.' ( Save: $'. ( ( $products->getRow( 0 )->price * $products->getRow( 3 )->quantity ) - $products->getRow( 3 )->price ) .' )',
				'contact_email' => $config->notification->email->fromAddress
			);
			// Replace placeholders.
			foreach ( $placeholders as $_key => $_value ) {
				$body = str_replace( ':'.$_key, $_value, $body );
			}
			return $body;
		}

		/**
		 * Email notification some days after plugin deinstallation.
		 * @internal Cron action.
		 */
		public function postInstallNotificationAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			$plugins = Table::_( 'plugins' )->getRecentlyInstalledPlugins();
			if ( count( $plugins ) ) {
				$helperEmail = $this->getHelper( 'email' );
				$path = array_shift( $this->view->getScriptPaths() ) . 'index/notification/';
				foreach ( $plugins as $plugin ) {
					$shop = Table::_( 'shops' )->get( $plugin->shop_id );
					$platform = Table::_( 'platforms' )->get( $plugin->platform );
					$enabled = Model::_( 'settings' )->getPlatformValue( $platform->id, 'post installation', 'bool' );
					if ( $enabled ) {
						// Prepare body.
						$name = $helperEmail->formTemplateName( $plugin, $path, 'recently_installed.phtml' );
						$body = $this->_replacePlaceholders2(
							$this->view->render( "index/notification/{$name}" ),
							$plugin
						);
						// Send email.
						$helperEmail->setSubject( $name )
							->getMailer()
							->clearRecipients()
							->addTo( $shop->email )
							->setBodyHtml( $body )
							->send()
							;
					}
				}
			}
		}

		/**
		 * Replaces placeholders in email body.
		 * @param string $body - body.
		 * @param object $plugin - installed plugin.
		 * @return string
		 */
		private function _replacePlaceholders2( $body, $plugin )
		{
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$placeholders = array (
				'application_name' => $plugin->name,
				'link_to_app_login' => $config->plugin->{$plugin->platform}->baseUrl . $filter->filter( $plugin->name ),
				'contact_email' => $config->notification->email->fromAddress
			);
			// Replace placeholders.
			foreach ( $placeholders as $_key => $_value ) {
				$body = str_replace( ':'.$_key, $_value, $body );
			}
			return $body;
		}

		/**
		 * Mail Chimp webhooks.
		 */
		public function mailchimpWebhooksAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
			switch ( $this->_getParam( 'type' ) )
			{
				case 'unsubscribe':
					$data = $this->_getParam( 'data' );
					$list_id = $data['list_id'];
					$email = $data['email'];
					$shop = Table::_( 'shops' )->getByEmail( $email );
					$user->setSetting( $newsType, $pluginId, 0 );
					break;
			}
		}

		/**
		 * Some db updates.
		 * Only for db patching.
		 */
		public function updateDbAction()
		{
			$this->getHelper( 'ViewRenderer' )->setNoRender( true );
		}
	}
