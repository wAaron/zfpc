<?php
	/**
	 * Tariff plan create form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.2
	 */
	class Admin_Form_TariffPlan_Create extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Default' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction(
					$this->getView()->url( array (
						'action' => 'create'
					) )
				)
				->setOptions( array (
					'id' => 'create-form',
					'class' => 'form-horizontal'
				) );
			// Platform.
			$multiOptions = array (
				'' => $this->getTranslator()->_( 'platform' )
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multiOptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform_id', array (
				'label' => 'platform',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Plugin.
			$multiOptions = array (
				'' => $this->getTranslator()->_( 'plugin' )
			);
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multiOptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin_id', array (
				'label' => 'plugin',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Payment plan.
			$multiOptions = array (
				'' => $this->getTranslator()->_( 'basic plan' )
			);
			$plans = Table::_( 'paymentPlans' )->fetchAll();
			foreach ( $plans as $plan ) {
				$multiOptions[ $plan->id ] = $plan->name;
			}
			$this->addElement( 'select', 'payment_plan_id', array (
				'label' => 'basic plan',
				'multiOptions' => $multiOptions,
				'validators' => array (
					'Int'
				)
			) );
			// Is visible.
			$this->addElement( 'checkbox', 'is_visible', array (
				'label' => 'is visible',
				'class' => 'form-checkbox',
				'validators' => array (
					'Int'
				)
			) );
			// Is free.
			$this->addElement( 'checkbox', 'is_free', array (
				'label' => 'is free',
				'class' => 'form-checkbox',
				'validators' => array (
					'Int'
				)
			) );
			// Name.
			$this->addElement( 'text', 'name', array (
				'label' => 'name',
				'required' => true
			) );
			// Product prices.
			$products = Table::_( 'paymentProducts' )->fetchAll();
			foreach ( $products as $product ) {
				$this->addElement( 'text', 'product_' . $product->id, array (
					'label' => $product->quantity .' '. $this->getTranslator()->_(  array ( 'month', 'months', $product->quantity ) ),
					'validators' => array (
						array ( 'Float', true, array (
							'locale' => 'en'
						) )
					)
				) );
			}
			// Options.
			$multiOptions = array ();
			$options = Table::_( 'options' )->fetchAll();
			foreach ( $options as $option ) {
				$multiOptions[ $option->id ] = $option->name;
			}
			$this->addElement( 'multiselect', 'options', array (
				'label' => 'options',
				'multiOptions' => $multiOptions
			) );
		}
	}
