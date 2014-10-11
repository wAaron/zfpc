<?php
	/**
	 * Instance settings form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class Admin_Form_Statistics_InstanceSettings extends Zend_Form
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
				->setAction( $this->getView()->url() )
				->setOptions( array (
					'id' => 'instance-settings-form',
					'class' => 'form-horizontal'
				) );
			// Trial period.
			$this->addElement( 'text', 'trial_period', array (
				'label' => 'trial period',
				'validators' => array (
					'Int'
				)
			) );
			// Current plan.
			$this->addElement( 'select', 'current_plan', array (
				'label' => 'current plan',
				'validators' => array (
					array (
						'Alnum', true, array (
							'allowWhiteSpace' => true
						)
					)
				)
			) );
			// Exclude from stat.
			$this->addElement( 'select', 'exclude_from_stat', array (
				'label' => 'exclude from stat',
				'validators' => array (
					'Int'
				),
				'multiOptions' => array (
					'0' => $this->getTranslator()->_( 'no' ),
					'1' => $this->getTranslator()->_( 'yes' )
				)
			) );
		}

		/**
		 * Enter description here ...
		 * @todo set plan id instead of plan name when the settings table be refactored.
		 * @param unknown_type $plans
		 */
		public function setPlans( $plans )
		{
			$multiOptions = array ();
			foreach ( $plans as $_plan ) {
				$multiOptions[ strtolower( $_plan->name ) ] = $_plan->name;
			}
			$this->current_plan->setMultiOptions( $multiOptions );
		}
	}
