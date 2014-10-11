<?php
	/**
	 * Transaction filter form.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.0
	 */
	class Admin_Form_Statistics_TransactionFilter extends Zend_Form
	{
		public function init()
		{
			$this->loadDefaultDecorators();
			$this->setDecorators( array ( 'FormElements', 'Form' ) );
			$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
			$this->setElementDecorators( array ( 'Filter' ) )
				->setTranslator(
					Zend_Registry::get( 'translate' )
				)
				->setAction( $this->getView()->url(
					array (
						'module' => 'admin',
						'controller' => 'statistics',
						'action' => 'transactions'
					), null, true
				) )
				->setOptions( array (
					'id' => 'filter-form'
				) );
			// Page.
			$this->addElement( 'hidden', 'page' );
			$this->getElement( 'page' )
				->removeDecorator( 'filter' )
				->addDecorator( 'ViewHelper' )
				;
			// Start date.
			$this->addElement( 'text', 'start_date', array (
				'label' => 'start date',
				'id' => 'start-date',
				'class' => 'form-control date-picker',
				'data-date-viewmode' => 'years',
				'data-date-format' => 'yyyy/mm/dd'
			) );
			// End date.
			$this->addElement( 'text', 'end_date', array (
				'label' => 'end date',
				'id' => 'end-date',
				'class' => 'form-control date-picker',
				'data-date-viewmode' => 'years',
				'data-date-format' => 'yyyy/mm/dd'
			) );
			// Platform.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all platforms' )
			);
			$platforms = Table::_( 'platforms' )->fetchAll();
			foreach ( $platforms as $platform ) {
				$multioptions[ $platform->id ] = $platform->title;
			}
			$this->addElement( 'select', 'platform', array (
				'label' => 'platform',
				'multioptions' => $multioptions
			) );
			// Plugin.
			$multioptions = array (
				'' => $this->getTranslator()->_( 'all plugins' )
			);
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multioptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin', array (
				'label' => 'plugin',
				'multioptions' => $multioptions
			) );
			// User.
			$this->addElement( 'text', 'user', array (
				'label' => 'user'
			) );
			// Buttons.
			$this->addElement( 'button', 'filter', array (
				'label' => '&nbsp;',
				'content' => $this->getTranslator()->_( 'apply filter' ),
				'class' => 'btn btn-primary form-control'
			) );
			$this->getElement( 'filter' )
				->getDecorator( 'filter' )
				->setOption( 'escape', false )
				;
		}
	}
