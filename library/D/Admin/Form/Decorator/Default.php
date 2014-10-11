<?php
	require_once 'Zend/Form/Decorator/Abstract.php';

	/**
	 * Default form decorator.
	 *
	 * @author Polyakov Ivan
	 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
	 * @link http://spur-i-t.com
	 * @package Admin
	 * @version 1.0.1
	 */
	class D_Form_Decorator_Admin_Default extends Zend_Form_Decorator_Abstract
	{
		/**
		 * Element's view.
		 * @var Zend_View
		 */
		private $_view;

		/**
		 * Builds label element.
		 * @return string
		 */
		public function buildLabel()
		{
			$label = $this->_element->getLabel();
			if ( $translator = $this->_element->getTranslator() ) {
				$label = $translator->translate( $label );
			}
			return $this->_view->formLabel(
				$this->_element->getName(), $label, array (
					'class' => 'col-sm-4 control-label'
				)
			);
		}

		/**
		 * Builds input element.
		 * @return string
		 */
		public function buildInput()
		{
			$helper = $this->_element->helper;
			if ( !$class = $this->_element->getAttrib( 'class' ) ) {
				$class = 'form-control';
			}
			$this->_element->setAttribs( array (
				'class' => $class,
				'helper' => null
			) );
			return $this->_view->$helper(
				$this->_element->getName(),
				$this->_element->getValue(),
				$this->_element->getAttribs(),
				$this->_element->options
			);
		}

		/**
		 * Returns error messages.
		 * @return string
		 */
		public function buildErrors() {
			if ( $messages = $this->_element->getMessages() ) {

				return $this->_view->formErrors( $messages );
			}
			return '';
		}

		/**
		 * Returns element description.
		 * @return string
		 */
		public function buildDescription() {
			$desc = $this->_element->getDescription();
			if ( $translator = $this->_element->getTranslator() ) {
				$desc = $translator->translate( $desc );
			}
			return ( empty ( $desc ) ? '' : $desc );
		}

		/**
		 * Renders element section.
		 * @internal Overrode
		 */
		public function render( $content )
		{
			$this->_view = $this->_element->getView();
			if ( ( $this->_view === null ) || ( !$this->_element instanceof Zend_Form_Element ) ) {
				return $content;
			}
			// Get section parts.
			$separator = $this->getSeparator();
			$placement = $this->getPlacement();
			$label = $this->buildLabel();
			$input = $this->buildInput();
			$errors = $this->buildErrors();
			$desc = $this->buildDescription();
			// Build element section.
$output = <<<SECTION
<div class="form-group">
	$label
	<div class="col-sm-7">$input</div>
	<div class="description col-sm-2">$desc</div>
	<div class="errors has-error"><span class="help-block">$errors</span></div>

</div>
SECTION;
			// Put everything into place and return.
			switch ( $placement ) {
				case self::PREPEND:
					return $output . $separator . $content;
				case self::APPEND:
				default:
					return $content . $separator . $output;
			}
		}
	}
