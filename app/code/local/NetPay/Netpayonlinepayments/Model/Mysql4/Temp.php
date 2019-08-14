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
 * Temp resource model
 *
 * @category	NetPay
 * @package		NetPay_Netpayonlinepayments
 * @author      NetPay Development Team
 */
class NetPay_Netpayonlinepayments_Model_Mysql4_Temp extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * constructor
	 * @access public
	 * @return void
	 * @author NetPay Development Team
	 */
	public function _construct()
	{
		$this->_init('netpayonlinepayments/temp', 'id');
	}
}
