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
 * Hosted Payment Form block
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author		NetPay Development Team
 * @copyright  	Copyright (c) 2014
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
 
class NetPay_Netpayonlinepayments_Block_Form extends Mage_Payment_Block_Form
{
 	/**
	 * @Purpose : construct function to set template for hosted from method
	 * @author  : NetPay Development Team
	 */
	protected function _construct() {	
		parent::_construct();
        $this->setTemplate('netpayonlinepayments/form.phtml');
    }
}