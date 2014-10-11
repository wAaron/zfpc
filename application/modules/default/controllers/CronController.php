<?php
	/**
	 * Cron activity collector.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Default
	 * @version 1.1.2
	 */
	class Default_CronController extends Zend_Controller_Action
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
		 * Cron start trigger.
		 * @internal CLI action.
		 */
		public function startAction()
		{
			$cron = Table::_( 'cron' )->getByKey(
				$this->_getParam( 'key' )
			);
			if ( $cron ) {
				Table::_( 'cronStat' )->insert( array (
					'cron_id' => $cron->id,
					'started' => new Zend_Db_Expr( 'NOW()' ),
					'hash' => $this->_getParam( 'hash' )
				) );
			}
		}

		/**
		 * Cron stop trigger.
		 * @internal CLI action.
		 */
		public function stopAction()
		{
			$cron = Table::_( 'cron' )->getByKey(
				$this->_getParam( 'key' )
			);
			if ( $cron ) {
				Table::_( 'cronStat' )->update( array (
					'finished' => new Zend_Db_Expr( 'NOW()' )
				), array (
					'cron_id = ?' => $cron->id,
					'hash = ?' => $this->_getParam( 'hash' )
				) );
			}
		}

		/**
		 * Clears old statistics.
		 * @internal Cron action.
		 */
		public function clearAction()
		{
			// Start cron.
			$this->getHelper( 'admin' )
				->startCronTask( 'clear-cron-stat' );
			// Clear.
			Table::_( 'cronStat' )->deleteOld();
			// Stop cron.
			$this->getHelper( 'admin' )
				->stopCronTask( 'clear-cron-stat' );
		}

		/**
		 * Sends notifications to admin about fails.
		 * @internal Cron action.
		 */
		public function notificationAction()
		{
			// Start cron.
			$this->getHelper( 'admin' )
				->startCronTask( 'cron-task-fails' );
			// Select failed stat.
			$config = Config::getInstance();
			$lastId = Table::_( 'variables' )->get( 'cron_stat_last_id' );
			$failedStat = Table::_( 'cronStat' )->getFailed( $lastId );
			if ( count( $failedStat ) ) {
				// Prepare fail list.
				$failList = array ();
				foreach ( $failedStat as $stat ) {
					$failList[ $stat->cron_id ][] = $stat;
					Table::_( 'variables' )->set( 'cron_stat_last_id', $stat->id );
				}
				// Send email.
				$this->view->failList = $failList;
				$body = $this->view->render( 'cron/notification/fail_list.phtml' );
				$this->getHelper( 'email' )
					->getMailer()
					->addTo( $config->cron->email->address )
					->setSubject(
						$config->cron->email->subject
					)
					->setBodyHtml( $body )
					->send()
					;
			}
			// Stop cron.
			$this->getHelper( 'admin' )
				->stopCronTask( 'cron-task-fails' );
		}
	}
