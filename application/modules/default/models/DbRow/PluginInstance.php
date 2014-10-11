<?php
	/**
	 * DbRow of plugin_installed DbTable.
	 *
	 * @todo settings storage for later retriving + update getTrialRest, remove func parameter.
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.4.2
	 */
	class Default_Model_DbRow_PluginInstance extends Zend_Db_Table_Row
	{
		/**
		 * Returns instance settings.
		 * @return stdClass
		 */
		public function getSettings() {
			$tableSettings = new Payment_Model_DbTable_Settings();
			return $tableSettings->getSettings( $this->shop_id, $this->plugin_id );
		}

		/**
		 * Returns instance setting.
		 * @param string $name - setting name.
		 * @return stdClass
		 */
		public function getSetting( $name ) {
			$tableSettings = new Payment_Model_DbTable_Settings();
			return $tableSettings->getSetting( $name, $this->shop_id, $this->plugin_id );
		}

		/**
		 * Sets instance settings.
		 * @param string $name - setting name.
		 * @param mixed $value - setting value.
		 * @return bool
		 */
		public function setSetting( $name, $value ) {
			$tableSettings = new Payment_Model_DbTable_Settings();
			return $tableSettings->setSetting( $name, $this->shop_id, $this->plugin_id, $value );
		}

		/**
		 * Returns the rest of instance trial period.
		 * @param integer $trialPeriod - instance trial period.
		 * @return integer
		 */
		public function getTrialRest( $trialPeriod )
		{
			$installDate = new DateTime( $this->installation_date );
			$interval = $installDate->diff( new DateTime() );
			$rest = $trialPeriod - $interval->d;
			return ( $rest > 0 ) ? $rest : 0;
		}

		/**
		 * Returns paid till date if no period was given.
		 * In other case, paid till date will be changed.
		 *
		 * @param integer $paidPeriod - paid period to adding.
		 * @return integer
		 */
		public function paidTill( $paidPeiod = null )
		{
			// Update, if new date was given.
			if ( $paidPeiod ) {
				if ( strtotime( $this->paid_till ) > time() ) {
					$timestamp = strtotime( $this->paid_till ) + $paidPeiod;
				} else {
					$timestamp = time() + $paidPeiod;
				}
				$this->paid_till = date( 'Y-m-d H:i:s', $timestamp );
				$this->save();
			}
			// Return current date.
			return strtotime( $this->paid_till );
		}

		/**
		 * Changes current paid till date of a plugin.
		 * @param mixed $factor - factor of the difference between plans.
		 */
		public function changePaidTill( $factor )
		{
			$trialRest = $this->getTrialRest(
				$this->getSetting( 'trial period' )->value
			) * SECONDS_PER_DAY;
			$rest = strtotime( $this->paid_till ) - $trialRest - time();
			Model::_( 'payment' )->setting( $this->shop_id, $this->plugin_id, 'old rest', $rest );
			Model::_( 'payment' )->setting( $this->shop_id, $this->plugin_id, 'new rest', ( $rest * $factor ) );
			$timestamp = time() + $trialRest + ( $rest * $factor );
			$this->paid_till = date( "Y-m-d H:i:s", $timestamp );
			$this->save();
		}
	}
