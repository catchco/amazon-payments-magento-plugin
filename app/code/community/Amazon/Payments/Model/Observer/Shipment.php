<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Observer_Shipment
{
    /**
     * Event: controller_response_redirect
     *
     * Alexa Delivery Notification
     */
    public function alexaDeliveryNotification(Varien_Event_Observer $observer)
    {
        $config = Mage::getSingleton('amazon_payments/config');
        if (!$config->isEnabled() || !$config->isAlexaEnabled()) {
            return;
        }

        $event = $observer->getEvent();

        /** @var Mage_Sales_Model_Order_Shipment */
        $shipment = $event->getShipment();

        if ($shipment->getOrder()->getPayment()->getMethod() != 'amazon_payments' || $shipment->getData('is_resave')) {
            return;
        }

        /** @var Mage_Sales_Model_Resource_Order_Shipment_Track_Collection */
        $tracks = $shipment->getTracksCollection();

        /** @var Mage_Sales_Model_Order_Shipment_Track */
        $track  = $tracks->getLastItem();

        $orderReference = $shipment->getOrder()->getPayment()->getAdditionalInformation('order_reference');

        // Has new tracking number?
        if ($track->getData() && $track->getCreatedAt() == $track->getUpdatedAt()) {
            $result = Mage::getSingleton('amazon_payments/alexa')
                ->addDeliveryNotification($orderReference, $track);
        }
    }
}
