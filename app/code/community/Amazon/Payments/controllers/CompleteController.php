<?php
/**
 * Amazon Payments SCA Complete Controller
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_CompleteController extends Mage_Core_Controller_Front_Action
{
    /**
     * Complete SCA checkout
     */
    public function checkoutAction()
    {
        $authenticationStatus = $this->getRequest()->getParam('AuthenticationStatus');

        switch ($authenticationStatus) {
            case 'Success':
                try {
                    $payment = Mage::getSingleton('checkout/session')->getPayment();
                    $payment['additional_information']['is_sca'] = true;
                    $this->_getCheckout()->savePayment($payment);
                    $this->_getCheckout()->saveOrder();
                    $this->_getCheckout()->getQuote()->save();
                    return $this->_redirect('checkout/onepage/success');
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError($e->getMessage());
                    if (Mage::getSingleton('checkout/session')->getIsAmazonRedirect()) {
                        $query = 'order_reference=' . $payment['additional_information']['order_reference'];
                        $this->_redirect('checkout/amazon_payments', array('_query' => $query));
                        return;
                    }
                    Mage::logException($e);
                }
                break;
            case 'Failure':
                Mage::getSingleton('core/session')->addError(
                    __('There was a problem with your payment. Your order hasn\'t been placed, and you haven\'t been charged.' .
                    '<script>setTimeout(function(){ amazon.Login.logout(); }, 1000);</script>')
                );
                break;
            case 'Abandoned':
                Mage::getSingleton('core/session')->addError(
                    __('Something\'s wrong with your payment method. To place your order, try another.')
                );
                $payment = Mage::getSingleton('checkout/session')->getPayment();
                $query = 'order_reference=' . $payment['additional_information']['order_reference'];
                $this->_redirect('checkout/amazon_payments', array('_query' => $query));
                return;
                break;
            default:
                Mage::getSingleton('core/session')->addError(
                    __('Something\'s wrong with your payment method. To place your order, try another.')
                );
                $this->_redirect('checkout/amazon_payments');
                return;
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Get checkout model
     *
     * @return Amazon_Payments_Model_Type_Checkout
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('amazon_payments/type_checkout');
    }
}
