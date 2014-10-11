<?php
	/**
	 * Plugin Center cron.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.3
	 */
	class Admin_CronController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_createForm = new Admin_Form_Cron_Create();
			$this->_editForm = new Admin_Form_Cron_Edit();
			$this->_entity = 'cron task';
			$this->_table = 'cron';
		}

		/**
		 * Cron task list.
		 */
		public function indexAction()
		{
			$config = Config::getInstance();
			// Select cron tasks and last stat.
			$lastStart = $lastFinish = $lastFail = array ();
			$tasks = Table::_( 'cron' )->getTasks();
			foreach ( $tasks as $task ) {
				$lastStart[ $task->id ] = Table::_( 'cronStat' )->getLastStart( $task->id );
				$lastFinish[ $task->id ] = Table::_( 'cronStat' )->getLastFinish( $task->id );
				$lastFail[ $task->id ] = Table::_( 'cronStat' )->getLastFail( $task->id, $task->max_exec_time );
			}
			// Prepare view.
			$this->view->tasks = $tasks;
			$this->view->lastStart = $lastStart;
			$this->view->lastFinish = $lastFinish;
			$this->view->lastFail = $lastFail;
			$this->view->criticalPeriod = $config->cron->failPeriodInHours * SECONDS_PER_HOUR;
			$this->view->formFilter = new Admin_Form_Cron_Filter();
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'cron tasks' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * Cron task statistics.
		 * @internal AJAX action.
		 */
		public function statisticsAction()
		{
			// Select stat.
			$stat = Table::_( 'cronStat' )->getForCron(
				$this->_getParam( 'id' )
			);
			// Select max_exec_time.
			if ( count( $stat ) ) {
				$this->view->max_exec_time = Table::_( 'cron' )->get(
					$stat->getRow( 0 )->cron_id
				)->max_exec_time;
			}
			// Prepare view.
			Zend_Layout::getMvcInstance()->disableLayout();
			$this->view->paginator = $this->getHelper( 'admin' )
				->getPaginator( $stat );
		}

		/**
		 * @internal Overrode
		 */
		protected function _postPopulationModification()
		{
			if ( $this->_editForm->getElement( 'platform_id' )->getValue() ) {
				$this->_editForm->getElement( 'plugin_id' )->setOptions( array (
					'disabled' => null
				) );
			}
		}
	}
