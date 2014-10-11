<?php
/**
 * Webhooks filter form.
 *
 * @author Kuksanau Ihnat
 * @copyright 2012 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_Form_Statistics_WebhooksFilter extends D_Admin_Form_StatisticsFilter
{
    public function init()
    {
		parent::init();
        $this->setOptions( array (
                'id' => 'webhooks-filter-form'
            ) );
    }
}
