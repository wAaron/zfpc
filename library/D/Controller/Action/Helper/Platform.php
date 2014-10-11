<?php
	require_once 'Zend/Controller/Action/Helper/Abstract.php';

	/**
	 * Multi-platform common helper.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Application
	 * @version 1.1.3
	 */
	class D_Controller_Action_Helper_Platform extends Zend_Controller_Action_Helper_Abstract
	{
		/**
		 * Forms title for layout.
		 * @param string $platform - platform name.
		 * @param string $pluginName - plugin name.
		 * @return string
		 */
		public function headTitle( $platform, $pluginName )
		{
			return sprintf(
				Zend_Registry::get( 'translate' )->_(
					strtolower( $platform ) .' plugin'
				), $pluginName
			);
		}

		/**
		 * Returns name of base plan assigned to current platform plan.
		 * @todo move somewhere.
		 * @param string $planName - platform plan name.
		 * @return string
		 */
		public function getBasePlan( $planName )
		{
			return Table::_( 'plans' )->fetchRow(
				Table::_( 'plans' )->select()
					->setIntegrityCheck( false )
					->from(
						array ( 'p' => Table::_( 'plans' )->info( 'name' ) ),
						'p.payment_plan_id'
					)
					->join(
						array ( 'pp' => Table::_( 'paymentPlans' )->info( 'name' ) ),
						"pp.id = p.payment_plan_id",
						array ( 'name' )
					)
					->where( "p.name = ?", $planName )
			)->name;
		}

		/**
		 * User validation.
		 * @param string $pluginName - plugin name.
		 * @return bool
		 */
		public function validateUser( $pluginName )
		{
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$cookieName = strtolower( $this->_platform ) .'-'. $filter->filter( $pluginName ) .'-user';
			$userId = $this->getRequest()
				->getCookie( $cookieName );
			$userId = intval( mcrypt_decrypt( MCRYPT_DES, $config->crypt->key, $userId, MCRYPT_MODE_ECB ) );
			if ( $userId ) {
				$user = Table::_( 'users' )->fetchRow( "`id` = $userId" );
				if ( $user ) {
					return $user;
				}
			}
			return false;
		}

		/**
		 * Sets user cookie.
		 * @param string $pluginName - plugin name.
		 * @param integer $userId - user id.
		 */
		public function setUserCookie( $pluginName, $userId )
		{
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			setcookie(
				strtolower( $this->_platform ) .'-'. $filter->filter( $pluginName ) .'-user',
				mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $userId, MCRYPT_MODE_ECB ),
				time() + SECONDS_PER_DAY,
				'/',
				$config->crypt->cookie->domain
			);
		}

		/**
		 * Sets installed cookie.
		 * @param string $pluginName - plugin name.
		 * @param integer $userId - user id.
		 */
		public function setInstalledCookie( $pluginName, $userId )
		{
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			setcookie(
				strtolower( $this->_platform ) .'-'. $filter->filter( $pluginName ) .'-installed',
				mcrypt_encrypt( MCRYPT_DES, $config->crypt->key, $userId, MCRYPT_MODE_ECB ),
				time() + SECONDS_PER_DAY,
				'/',
				$config->crypt->cookie->domain
			);
		}

		/**
		 * Returns plugin home url.
		 * @param string $name - plugin name.
		 * @return string
		 */
		public function getHomeUrl( $name )
		{
			$config = Config::getInstance();
			$filter = new D_Filter_PluginDirectory();
			$url = $config->plugin->{strtolower( $this->_platform )}->baseUrl . $filter->filter( $name );
			return $url;
		}

		/**
		 * Redirects to a plugin.
		 * @param string $name - plugin name.
		 * @param string $action - plugin action.
		 */
		public function gotoPlugin( $name, $action = '' )
		{
			if ( $action ) {
				switch ( $action ) {
					case 'install':
						$action = '/app/install';
						break;
				}
			}
			else {
				$action = '/';
			}
			return Zend_Controller_Action_HelperBroker::getStaticHelper( 'Redirector' )->gotoUrl(
				$this->getHomeUrl( $name ) . $action
			);
		}
	}
