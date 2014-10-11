<?php
/**
 * Filter form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Webhooks_Filter extends D_Admin_Form_Filter
{
	public function init()
	{
		parent::init();
		$this->setAction( $this->getView()->url(
			array (
				'module' => 'admin',
				'controller' => 'webhooks',
				'action' => 'index'
			), null, true
		) );

		//topic
        $topicValues = Table::_('webhooks')->getTopicValues();
        $topicValues = array('' => 'All topics') + $topicValues;
		$this->addElement( 'select', 'topic', array (
			'label' => 'Topic',
			'multioptions' => $topicValues,
			'value' => ''
		) );

        //registered
        $this->addElement( 'select', 'registered', array (
            'label' => 'Registered via Api',
            'multioptions' => array(
                '' => $this->getTranslator()->_( 'any state' ),
                'Yes' => 'Yes',
                'No' => 'No'
            ),
            'value' => ''
        ) );
	}
}
