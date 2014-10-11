<?php
/**
 * Password Confirm Validator
 *
 * @author Kuksanau Ihnat, SpurIT <contact@spur-i-t.com>
 * @copyright Copyright (c) 2013 SpurIT <contact@spur-i-t.com>, All rights reserved
 * @link http://spur-i-t.com
 * @package Application
 * @version 1.0.0
 */
class D_Validate_PasswordConfirmation extends Zend_Validate_Abstract {

    const NOT_MATCH = 'notMatch';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
		self::NOT_MATCH => 'Password confirmation does not match'
	);

    /**
     * @param mixed $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null) {
		$value = (string) $value;
		$this->_setValue($value);

		if (is_array($context)) {
			if (
				isset($context['password_confirm']) &&
				($value == $context['password_confirm'])) {
				return true;
			}
		} elseif (is_string($context) && ($value == $context)) {
			return true;
		}

		$this->_error(Zend_Registry::get( 'translate' )->_( self::NOT_MATCH ));
		return false;
	}

}