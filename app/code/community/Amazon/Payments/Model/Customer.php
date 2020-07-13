<?php
/**
 * Login with Amazon Customer Model
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Customer extends Mage_Customer_Model_Customer
{
    protected $isRedirect = false;

    /**
     * Log user in via Amazon token
     *
     * @param $token
     *   Amazon Access Token
     * @return $this
     */
    public function loginWithToken($token)
    {
        $amazonProfile = $this->getAmazonProfile($token);

        if ($amazonProfile && isset($amazonProfile['user_id']) && isset($amazonProfile['email'])) {
            $customerSession = Mage::getSingleton('customer/session');
            $checkoutSession = Mage::getSingleton('checkout/session');

            // Load Amazon Login association
            $row = Mage::getModel('amazon_payments/login')->load($amazonProfile['user_id'], 'amazon_uid');

            if ($row->getLoginId()) {
                // Load customer by id
                $this->setWebsiteId(Mage::app()->getWebsite()->getId())->load($row->getCustomerId());
            } else {
                // Load customer by email
                $this->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($amazonProfile['email']);
            }

            $customerSession->setAmazonProfile($amazonProfile);

            // If Magento customer account exists and there is no association, then the Magento account
            // must be verified, as Amazon does not verify email addresses.
            if (!$row->getLoginId() && $this->getId()) {
                $checkoutSession->setAmazonAccessTokenVerify($token);
                $this->isRedirect = true;
                return $this;
            }
            // Create account if applicable and log user in
            else {
                // Create account
                if (!$this->getId()) {
                    $this->createCustomer($amazonProfile);
                }
                $customerSession->setCustomerAsLoggedIn($this);

                // Use Pay with Amazon for checkout (if Amazon_Payments enabled)
                $checkoutSession->setAmazonAccessToken($token);
            }

            // Set quote customer ID for 1.9.4.2 security fix
            if ($this->getId() && !$checkoutSession->getQuote()->getCustomerId()) {
                $checkoutSession->getQuote()->setCustomerId($customerSession->getId())->save();
            }

        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        return $this->isRedirect;
    }

    /**
     * Get Amazon Profile
     */
    public function getAmazonProfile($token)
    {
        return Mage::getModel('amazon_payments/login')->request('user/profile?access_token=' . urlencode($token));
    }

    /**
     * Create a new customer
     *
     * @param array $amazonProfile
     *   Associative array containing email and name
     * @return object $customer
     */
    public function createCustomer($amazonProfile)
    {
        list($firstName, $lastName) = Amazon_Payments_Helper_Data::getAmazonName($amazonProfile['name']);

        try {
            $this
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->setEmail($amazonProfile['email'])
                ->setPassword($this->generatePassword(8))
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setConfirmation(null)
                ->setIsActive(1)
                ->save()
                ->sendNewAccountEmail('registered', '', Mage::app()->getStore()->getId());

            // If email confirmation required, must resave customer
            if ($this->isConfirmationRequired()) {
                $this->setConfirmation(null)->save();
            }

            $this->createAssociation($amazonProfile, $this->getId());

        }
        catch (Exception $ex) {
            Mage::logException($ex);
        }

        return $this;
    }

    /**
     * Create association between Amazon Profile and Customer
     */
    public function createAssociation($amazonProfile, $customer_id)
    {
        Mage::getModel('amazon_payments/login')
            ->setCustomerId($customer_id)
            ->setAmazonUid($amazonProfile['user_id'])
            ->save();

    }

}

