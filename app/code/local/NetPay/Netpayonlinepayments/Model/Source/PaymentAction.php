<?php
/**
 * NetPay_Netpayonlinepayments extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category   	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @copyright  	Copyright (c) 2013
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * PaymentAction Source model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Source_PaymentAction
{
	//const REQUEST_TYPE_AUTH_CAPTURE = 'authorize_capture';
	const REQUEST_TYPE_AUTH_CAPTURE = 'order';
	
	public function toOptionArray()
    {
        return array(
            array(
                'value' => self::REQUEST_TYPE_AUTH_CAPTURE,
                'label' => Mage::helper('netpayonlinepayments')->__('Authorize and Capture')
            ),
        );
    }
}
