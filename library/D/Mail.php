<?php
/**
 * Base class for PC mail client
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package D
 * @version 1.0.0
 */
class D_Mail extends Zend_Mail
{
    /**
     * add additional behaviour to standard mailer
     * @param null $transport
     * @return void|Zend_Mail
     */
    public function send( $transport = null )
    {
        //standard behaviour
        parent::send( $transport );

        //save to emails db table as sent by app
        $bodyHtml = $this->getBodyHtml()->getRawContent();
        Table::_( 'emails' )->saveEmail( array(
            'emailFrom' => $this->_from,
            'emailTo' => is_array( $this->_to ) ? reset( $this->_to ) : $this->_to,
            'emailSubject' => $this->_subject,
            'emailMessage' => $bodyHtml ? gzcompress( $bodyHtml ) : gzcompress('...') ,
            'emailNotSend' => true,
        ) );

        return $this;
    }
}
