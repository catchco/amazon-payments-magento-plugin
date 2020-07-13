<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Alexa
{
    protected $logFile = 'amazon_delivery_notifications.log';
    private $carriers;

    public function __construct()
    {
        // Map carrier title to carrier code
        $path = Mage::getBaseDir('lib') . DS . 'AmazonPayV2' . DS . 'amazon-pay-delivery-tracker-supported-carriers.csv';
        $csv = new Varien_File_Csv();
        $this->carriers = array_change_key_case($csv->getDataPairs($path), CASE_LOWER);
    }

    /**
     * Submit Alexa delivery notification
     *
     * @param $orderReference string Amazon's OrderReferenceId
     * @param $track Mage_Sales_Model_Order_Shipment_Track
     */
    //public function addDeliveryNotification($carrierCode, $trackingNumber)
    public function addDeliveryNotification($orderReference, $track)
    {
        /** @var Mage_Sales_Model_Order_Shipment */
        $shipment = $track->getShipment();

        // Shipment created through API, not admin
        $isMageApi = Mage::getSingleton('api/server')->getAdapter();

        $amazonpay_config = array(
            'public_key_id' => $this->getConfig()->getAlexaPublicKeyId(),
            'private_key'   => $this->getConfig()->getAlexaPrivateKey(),
            'sandbox'       => false, // deliveryTrackers not available in sandbox mode
            'region'        => $this->getConfig()->getRegion()
        );

        $client = new AmazonPayV2_Client($amazonpay_config);

        $trackerNumber = $track->getTrackNumber();
        $carrierCode   = $this->mapCarrierCode($track->getCarrierCode(), $track->getTitle());

        $payload = array(
            'amazonOrderReferenceId' => $orderReference,
            'deliveryDetails' => array(array(
                'trackingNumber' => $trackerNumber,
                'carrierCode' => $carrierCode,
            ))
        );

        $payload = json_encode($payload);
        $result = array();

        try {
            $result = $client->deliveryTrackers($payload);
        }
        catch (Exception $exception) {
            Mage::logException($exception);
            Mage::throwException($exception);
        }

        // Is transaction/debug logging enabled?
        if (Mage::getStoreConfig('payment/amazon_payments/debug')) {
            Mage::log(print_r($result, true) . "\n", null, $this->logFile);
        }

        if (!empty($result['status'])) {
            if ($result['status'] == '200') {
                $comment = Mage::helper('core')->__('Amazon Pay has received shipping tracking information for carrier %s and tracking number %s.',
                    $carrierCode, $trackerNumber);

                $shipment->addComment($comment);
                $shipment->setData('is_resave', true);
                $shipment->save();

                if (!$isMageApi) {
                    Mage::getSingleton('adminhtml/session')->addSuccess($comment);
                }
            } else {
                if (!$isMageApi) {
                    $response = json_decode($result['response']);
                    $errorMessage = 'Alexa Delivery Tracker returned a ' . $result['status'] . ' error: ' . "\n" .
                        $response->reasonCode . ': ' . $response->message;

                    if (strpos($response->message, 'missing key') !== false) {
                        $errorMessage = 'Please add the missing Private/Public key value in the Alexa Delivery Notification settings in Amazon Pay to enable Delivery Notifications.';
                    }

                    Mage::getSingleton('adminhtml/session')->addNotice($errorMessage);
                }
            }
        }

        return $result;
    }


    /**
     * Generate and save new public/private keys
     */
    public function generateKeys()
    {
        $rsa = new Zend_Crypt_Rsa;
        $keys = $rsa->generateKeys(array('private_key_bits' => 2048, 'privateKeyBits' => 2048, 'hashAlgorithm' => 'sha1'));

        Mage::getConfig()
            ->saveConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PUBKEY,
                $keys['publicKey'],'default', 0)
            ->saveConfig(Amazon_Payments_Model_Config::CONFIG_XML_PATH_ALEXA_PRIVKEY,
                Mage::helper('core')->encrypt($keys['privateKey']), 'default', 0);
        Mage::app()->cleanCache();
    }

    /**
     * Get Amazon Payments config
     *
     * @return Amazon_Payments_Model_Config
     */
    private function getConfig()
    {
        return Mage::getSingleton('amazon_payments/config');
    }

    /**
     * Return carrier code
     */
    private function mapCarrierCode($code, $title = '')
    {
        if (isset($this->carriers[strtolower($title)])) {
            return $this->carriers[strtolower($title)];
        }

        if (stripos($code, 'usps') !== false) {
            return 'USPS';
        }

        if (stripos($code, 'ups') !== false) {
            return 'UPS';
        }

        if (stripos($code, 'fedex') !== false) {
            return 'FEDEX';
        }
        return strtoupper($code);
    }


}
