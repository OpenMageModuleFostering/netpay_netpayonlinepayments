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
 * PaymentMode Source model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Source_PaymentMode
{
	const PAYMENT_LIVE = '1';
	const PAYMENT_TEST = '2';	

	public function toOptionArray()
    {
        return array
        (
            array(
                'value' => self::PAYMENT_LIVE,
                'label' => Mage::helper('netpayonlinepayments')->__('Live')
            ),
			array(
                'value' => self::PAYMENT_TEST,
                'label' => Mage::helper('netpayonlinepayments')->__('Test')
            )
        );
    }
}
