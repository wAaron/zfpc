<?php
/**
 * Common filter form for items
 *
 * @author Kuksanau Ihnat
 * @copyright 2014 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class D_Admin_Form_Filter extends Zend_Form
{
	/**
	 * list of form elements by default
	 * @var array
	 */
	private $_defaultElementsList = array( 'platform', 'plugin', 'filter' );

	public function init()
	{
		$this->loadDefaultDecorators();
		$this->setDecorators( array ( 'FormElements', 'Form' ) );
		$this->addElementPrefixPath( 'D_Form_Decorator_Admin', 'D/Admin/Form/Decorator/', 'decorator' );
		$this->setElementDecorators( array ( 'Filter' ) )
			->setTranslator(
				Zend_Registry::get( 'translate' )
			)
			->setAction( $this->getView()->url())
			->setOptions( array (
				'id' => 'filter-form'
			) );
		// Page.
		$this->addElement( 'hidden', 'page' );
		$this->getElement( 'page' )
			->removeDecorator( 'filter' )
			->addDecorator( 'ViewHelper' )
		;

		if(in_array( 'platform', $this->_defaultElementsList )){
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
		}

		if(in_array( 'plugin', $this->_defaultElementsList )){
			// Plugin.
			$multioptions = array ( '' => $this->getTranslator()->_( 'all plugins' ) );
			$plugins = Table::_( 'plugins' )->fetchAll();
			foreach ( $plugins as $plugin ) {
				$multioptions[ $plugin->id ] = $plugin->name;
			}
			$this->addElement( 'select', 'plugin', array (
				'label' => 'Plugin',
				'multioptions' => $multioptions
			) );
		}


		if(in_array( 'filter', $this->_defaultElementsList )){
			// Buttons.
			$this->addElement( 'button', 'filter', array (
				'label' => '&nbsp;',
				'content' => $this->getTranslator()->_( 'apply filter' ),
				'class' => 'btn btn-primary form-control',
				'order' => 10 //to be at the end of form
			) );
			$this->getElement( 'filter' )
				->getDecorator( 'filter' )
				->setOption( 'escape', false )
			;
		}
	}

	/**
	 * set list of default elements
	 * @param array $elements
	 */
	protected function setDefaultElementsList( $elements = array() )
	{
		$this->_defaultElementsList = array();

		if(is_array($elements)){
			foreach( $elements as $elementName)
			{
				$this->_defaultElementsList[] = $elementName;
			}
		}elseif(is_string( $elements )){
			$this->_defaultElementsList[] = $elements;
		}
	}
}
