<?php
	/**
	 * Tariff plans controller.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.3
	 */
	class Admin_TariffPlansController extends D_Admin_Controller_Abstract
	{
		/**
		 * Initialization.
		 */
		public function init()
		{
			parent::init();
			$this->_createForm = new Admin_Form_TariffPlan_Create();
			$this->_editForm = new Admin_Form_TariffPlan_Edit();
			$this->_entity = 'plan';
			$this->_table = 'plans';
		}

		/**
		 * Plan list.
		 */
		public function indexAction()
		{
			$config = Config::getInstance();
			// Prepare filter params.
			$formFilter = new Admin_Form_TariffPlan_Filter();
			$filterParams = array ();
			if ( $this->_request->isPost() ) {
				if ( $formFilter->isValid( $this->_request->getPost() ) ) {
					$filterParams = $formFilter->getValues();
					$this->view->filtered = true;
				}
			}
			// Load plans.
			$page = $this->_getParam( 'page', 1 );
			$plans = Table::_( 'plans' )->getAdminList( $filterParams );
			$paginator = Zend_Paginator::factory( $plans );
			$paginator->setItemCountPerPage(
				$config->plugin->center->admin->itemsPerPage
			);
			$paginator->setCurrentPageNumber( $page );
			// Prepare view.
			$this->view->paginator = $paginator;
			$this->view->formFilter = $formFilter;
			$this->view->platformPlugins = $this->getHelper( 'admin' )
				->getPlatformPlugins();
			$this->view->title = Zend_Registry::get( 'translate' )->_( 'tariff plans' );
			$this->view->headTitle( $this->view->title );
		}

		/**
		 * @internal Overrode
		 */
		protected function _createEntity()
		{
			$values = $this->_createForm->getValues();
			// Add plan.
			$planId = Table::_( 'plans' )->insert( array (
				'payment_plan_id' => $values['payment_plan_id'],
				'plugin_id' => $values['plugin_id'],
				'name' => $values['name'],
				'is_free' => $values['is_free'],
				'is_visible' => $values['is_visible']
			) );
			// Add prices.
			$products = Table::_( 'paymentProducts' )->fetchAll();
			foreach ( $products as $product ) {
				Table::_( 'paymentPrices' )->insert( array (
					'plugin_id' => $values['plugin_id'],
					'payment_plan_id' => $values['payment_plan_id'],
					'product_id' => $product->id,
					'price' => $values['is_free'] ? 'free' : (float) $values['product_' . $product->id ]
				) );
			}
			// Add options.
			if ( !empty ( $values['options'] ) ) {
				foreach ( $values['options'] as $optionId ) {
					Table::_( 'plansToOptions' )->insert( array (
						'plan_id' => $planId,
					 	'option_id' => $optionId
					) );
				}
			}
		}

		/**
		 * @internal Overrode
		 */
		protected function _setEntity( $id )
		{
			$plan = Table::_( $this->_table )->get( $id );
			$values = $plan->toArray();
			// Set platform.
			$plugin = Table::_( 'plugins' )->get( $plan->plugin_id );
			$platform = Table::_( 'platforms' )->get( $plugin->platform );
			$values['platform_id'] = $platform->id;
			// Set prices.
			$products = $plan->getProducts();
			foreach ( $products as $product ) {
				$values['product_' . $product->id ] = $product->price;
			}
			// Set options.
			$options = $plan->getOptions();
			foreach ( $options as $option ) {
				$values['options'][] = $option->id;
			}
			// Populate.
			$this->_editForm->populate( $values );
		}

		/**
		 * @internal Overrode
		 */
		protected function _updateEntity( $id )
		{
			$values = $this->_editForm->getValues();
			// Update plan.
			$plan = Table::_( $this->_table )->get( $id );
			// TODO. remove this crutch.
			if ( $plan->name != $values['name'] ) {
				Table::_( 'paymentSettings' )->update( array (
					'value' => strtolower( $values['name'] )
				), array (
					'plugin_id = ?' => $plan->plugin_id,
					'name = ?' => 'current plan',
					'value = ?' => strtolower( $plan->name )
				) );
			}
			$plan->is_free = $values['is_free'];
			$plan->is_visible = $values['is_visible'];
			$plan->name = $values['name'];
			$plan->save();
			// Update prices.
			$products = $plan->getProducts();
			foreach ( $products as $product ) {
				Table::_( 'paymentPrices' )->update( array (
					'price' => $values['is_free'] ? 'free' : (float) $values['product_' . $product->id ]
				), array (
					'plugin_id = ?' => $plan->plugin_id,
					'payment_plan_id = ?' => $plan->payment_plan_id,
					'product_id = ?' => $product->id,
				) );
			}
			// Update options.
			$currentOptions = array ();
			$options = $plan->getOptions();
			if ( !empty ( $options ) ) {
				foreach ( $options as $option ) {
					if ( in_array( $option->id, $values['options'] ) ) {
						$currentOptions[] = $option->id;
					} else {
						Table::_( 'plansToOptions' )->delete( array (
							'plan_id = ?' => $id,
						 	'option_id = ?' => $option->id
						) );
					}
				}
			}
			if ( !empty ( $values['options'] ) ) {
				foreach ( $values['options'] as $optionId ) {
					if ( !in_array( $optionId, $currentOptions ) ) {
						Table::_( 'plansToOptions' )->insert( array (
							'plan_id' => $id,
						 	'option_id' => $optionId
						) );
					}
				}
			}
		}

		/**
		 * Checks whether a plan can be deleted before it.
		 * @internal Overrode
		 */
		protected function _deleteEntity( $id )
		{
			$plan = Table::_( $this->_table )->get( $id );
			$row = Table::_( 'paymentSettings' )->fetchRow( array (
				'plugin_id = ?' => $plan->plugin_id,
				'name = ?' => 'current plan',
				'value = ?' => strtolower( $plan->name )
			) );
			if ( !$row ) {
				parent::_deleteEntity( $id );
			}
		}
	}
