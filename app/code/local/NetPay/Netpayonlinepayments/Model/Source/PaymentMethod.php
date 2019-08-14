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
 * PaymentMethod Source model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Source_PaymentMethod
{
	const PAYMENT_MODE_HOSTED_PAYMENT_FORM = 'hosted';
	const PAYMENT_MODE_API = 'api';	

	public function toOptionArray()
    {
        return array
        (
            array(
                'value' => self::PAYMENT_MODE_HOSTED_PAYMENT_FORM,
                'label' => Mage::helper('netpayonlinepayments')->__('Hosted Payment Form')
            ),
			array(
                'value' => self::PAYMENT_MODE_API,
                'label' => Mage::helper('netpayonlinepayments')->__('API Integartion')
            )
        );
    }
}
