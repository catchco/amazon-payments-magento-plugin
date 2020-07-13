<?php
/**
 * Amazon payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Alexakeycomment extends Mage_Core_Model_Config_Data
{
    /**
     * Return dynamic help/comment text
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $helper = Mage::helper('adminhtml');
        $generateUrl = $helper->getUrl('adminhtml/amazon_alexa/generate');
        $downloadUrl = $helper->getUrl('adminhtml/amazon_alexa/download');

        $storeId = Mage::getSingleton('adminhtml/config_data')->getStore();
        $pubkey  = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY, $storeId);
        $privkey = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PRIVKEY, $storeId);

        if (!$privkey) {
            return '<a href="' . $generateUrl . '">' . $helper->__('Generate a new public/private key pair for Amazon Pay') . '</a>';
        }
        else if ($pubkey) {
            return '<a href="' . $downloadUrl . '">' . $helper->__('Download Public Key') . '</a>';
        }
    }
}
