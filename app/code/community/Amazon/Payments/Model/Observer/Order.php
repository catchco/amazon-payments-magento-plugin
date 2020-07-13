<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Observer_Order
{
    /**
     * Event: sales_order_place_after
     *
     * Programmatically update customer address book with Amazon address
     */
    public function updateCustomerAddress(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order    = $observer->getEvent()->getOrder();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $order->getCustomer();
        $payment  = $order->getPayment();

        if ($customer && $customer->getId() && $payment->getMethodInstance()->getCode() == 'amazon_payments') {
            $billingAddress = $order->getBillingAddress();
            $customerAddress = $order->getShippingAddress() ? $order->getShippingAddress() : $order->getBillingAddress();

            $newAddress = Mage::getModel('customer/address')
                ->addData($customerAddress->getData())
                ->setCustomerId($customer->getId())
      			    ->setSaveInAddressBook('1');

            $newAddressBilling = Mage::getModel('customer/address')
                ->addData($billingAddress->getData())
                ->setCustomerId($customer->getId())
                ->setSaveInAddressBook('1');

            // See if billing and shipping are the same
            if ($newAddress->getPostcode() == $newAddressBilling->getPostcode() && $newAddress->getStreet() == $newAddressBilling->getStreet()) {

                // Evaluate for existing address
                foreach ($customer->getAddresses() as $address) {
                    if ($address->getPostcode() == $newAddress->getPostcode() && $address->getStreet() == $newAddress->getStreet()) {

                        // Set default if exists
                        if (!$customer->getDefaultBilling()) {
                            $address->setIsDefaultBilling('1');
                        }
                        if (!$customer->getDefaultShipping()) {
                            $address->setIsDefaultShipping('1');
                        }
                        try {
                            $address->save();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                        return;
                    }
                }

                // Set new default billing address
                if (!$customer->getDefaultBilling()) {
                    $newAddress->setIsDefaultBilling('1');
                }

                // Create new default shipping address
                if (!$customer->getDefaultShipping()) {
                    $newAddress->setIsDefaultShipping('1');
                }

                try {
                    $newAddress->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            // Create multiple addresses if different
            else {
                $foundBilling = false;
                $foundShipping = false;
                // Check for existing addresses and set as default
                foreach ($customer->getAddresses() as $address) {
                    if (!$foundBilling) {
                        if ($address->getPostcode() == $newAddressBilling->getPostcode() && $address->getStreet() == $newAddressBilling->getStreet()) {
                            if (!$customer->getDefaultBilling()) {
                                $address->setIsDefaultBilling('1');
                                $foundBilling = true;
                                try {
                                    $address->save();
                                } catch (Exception $e) {
                                    Mage::logException($e);
                                }
                            }
                        }
                    }
                    if (!$foundShipping) {
                        if ($address->getPostcode() == $newAddress->getPostcode() && $address->getStreet() == $newAddress->getStreet()) {
                            if (!$customer->getDefaultShipping()) {
                                $address->setIsDefaultShipping('1');
                                $foundShipping = true;
                                try {
                                    $address->save();
                                } catch (Exception $e) {
                                    Mage::logException($e);
                                }
                            }
                        }
                    }
                    // If both addresses already exist, we're done
                    if ($foundBilling && $foundShipping) return;
                }

                // Create new default billing address
                if (!$foundBilling && !$customer->getDefaultBilling()) {
                    $newAddressBilling->setIsDefaultBilling('1');
                    try {
                        $newAddressBilling->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }

                // Create new default shipping address
                if (!$foundShipping && !$customer->getDefaultShipping()) {
                    $newAddress->setIsDefaultShipping('1');
                    try {
                        $newAddress->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
        }
    }


    /**
     * Event: sales_order_save_commit_after
     *
     * Close Amazon ORO
     */
    public function closeAmazonOrder(Varien_Event_Observer $observer)
    {
        $order   = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE && $order->getOrigData('state') != Mage_Sales_Model_Order::STATE_COMPLETE
            && $payment->getMethodInstance()->getCode() == 'amazon_payments') {
            Mage::getModel('amazon_payments/api')->closeOrderReference($payment->getAdditionalInformation('order_reference'));
        }
    }
}