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
 * Cctype Source model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Source_Cctype
{
	const CODE_VISA = 'VISA';
	const CODE_VISAUK = 'VISAUK';
	const CODE_ELEC = 'ELEC';
	const CODE_MCRD = 'MCRD';
	const CODE_MCDB = 'MCDB';
	const CODE_MSTO = 'MSTO';
	const CODE_AMEX = 'AMEX';
	const CODE_DINE = 'DINE';

	public function toOptionArray()
    {
        return array
        (
            array(
                'value' => self::CODE_VISA,
                'label' => Mage::helper('netpayonlinepayments')->__('Visa')
            ),
			array(
                'value' => self::CODE_VISAUK,
                'label' => Mage::helper('netpayonlinepayments')->__('Visa Debit UK')
            ),
			array(
                'value' => self::CODE_ELEC,
                'label' => Mage::helper('netpayonlinepayments')->__('Visa Electron')
            ),
			array(
                'value' => self::CODE_MCRD,
                'label' => Mage::helper('netpayonlinepayments')->__('MasterCard')
            ),
			array(
                'value' => self::CODE_MCDB,
                'label' => Mage::helper('netpayonlinepayments')->__('MasterCard Debit')
            ),
			array(
                'value' => self::CODE_MSTO,
                'label' => Mage::helper('netpayonlinepayments')->__('Maestro')
            ),
			array(
                'value' => self::CODE_AMEX,
                'label' => Mage::helper('netpayonlinepayments')->__('American Express')
            ),
			array(
                'value' => self::CODE_DINE,
                'label' => Mage::helper('netpayonlinepayments')->__('Diners')
            )
        );
    }
	
	public function getAllOptions()
    {
        return array
        (
            self::CODE_VISA => Mage::helper('netpayonlinepayments')->__('Visa'),
			self::CODE_VISAUK => Mage::helper('netpayonlinepayments')->__('Visa Debit UK'),
			self::CODE_ELEC => Mage::helper('netpayonlinepayments')->__('Visa Electron'),
			self::CODE_MCRD => Mage::helper('netpayonlinepayments')->__('MasterCard'),
			self::CODE_MCDB => Mage::helper('netpayonlinepayments')->__('MasterCard Debit'),
			self::CODE_MSTO => Mage::helper('netpayonlinepayments')->__('Maestro'),
			self::CODE_AMEX => Mage::helper('netpayonlinepayments')->__('American Express'),
			self::CODE_DINE => Mage::helper('netpayonlinepayments')->__('Diners')
        );
    }	
	
	
	
}
