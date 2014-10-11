<?php
/**
 * Common filter form for stats
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class D_Admin_Form_StatisticsFilter extends Zend_Form
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
			->setAction( $this->getView()->url() );
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
	}
}
