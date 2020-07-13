<?php
/**
 * Amazon Payments SimplePath
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Adminhtml_Amazon_AlexaController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Download public key
     */
    public function downloadAction()
    {
        $storeId = Mage::getSingleton('adminhtml/config_data')->getStore();
        $pubkey  = Mage::getStoreConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY, $storeId);
        $file = 'amazon_public_key.pub';

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pubkey));
        $this->getResponse()->setBody($pubkey);
    }

    /**
     * Generate public and private key
     */
    public function generateAction()
    {
        $storeId = Mage::getSingleton('adminhtml/config_data')->getStore();
        Mage::getModel('amazon_payments/alexa')->generateKeys();

        $downloadUrl = $this->getUrl('adminhtml/amazon_alexa/download');
        Mage::getSingleton('adminhtml/session')->addSuccess(
            $this->__('Your Amazon Pay public/private keypair has been generated for Alexa Delivery Notification.')
            //'<a href="%s">Download Public Key</a>.', $downloadUrl)
        );
        $this->_redirect('adminhtml/system_config/edit/section/payment');
    }

}
