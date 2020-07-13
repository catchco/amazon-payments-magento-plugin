<?php
/**
 * Amazon payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Alexaemailcomment extends Mage_Core_Model_Config_Data
{
    /**
     * Return dynamic help/comment text
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $helper = Mage::helper('adminhtml');

        $storeId = Mage::getSingleton('adminhtml/config_data')->getStore();
        $pubkeyid  = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY_ID, $storeId);
        $pubkey  = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY, $storeId);
        $privkey = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PRIVKEY, $storeId);

        if (!$pubkeyid) {
            if (!$pubkey) {
                Mage::getModel('amazon_payments/alexa')->generateKeys();
                $pubkey = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY, $storeId);
            }
            if ($privkey) {
                $merchantId = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_SELLER_ID, $storeId);
                $subject = rawurlencode('Request for Amazon Pay Public Key ID for ' . $merchantId);
                $body = rawurlencode("Merchant ID: $merchantId\n\nPublic Key:\n\n$pubkey");
                return __('Please <a href="%s">contact</a> Amazon Pay to receive the Public Key ID.',
                    'mailto:Amazon-pay-delivery-notifications@amazon.com?subject=' . $subject . '&body=' . $body);
            }
        }
    }
}
