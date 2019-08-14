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
 * Secureredirect Block
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Development Team
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
 
class NetPay_Netpayonlinepayments_Block_Secureredirect extends Mage_Core_Block_Abstract
{
	/**
	  * @Purpose : Function to return block HTML ( Retun 3d secure from to be submitted on payment gatway for authentication )
	  * @author  : NetPay Development Team
	 */
	 protected function _toHtml() {
		$html = self::_redirectFor3dSecureAuthentication();
		return $html;
	 }
    
	/**
	  * @Purpose : Function to decode 3d Secure form recieved in response
	  * @Outputs : Return decoded auto submit from  
	  * @author  : NetPay Development Team
	 */
    private function _redirectFor3dSecureAuthentication() {
    	
		$html = '';
		$dSecureForm	= Mage::getSingleton('checkout/session')->getDsecureForm();
		$html = base64_decode($dSecureForm);
		Mage::getSingleton('checkout/session')->setDsecureForm(null);
		return $html;
    }
	
}