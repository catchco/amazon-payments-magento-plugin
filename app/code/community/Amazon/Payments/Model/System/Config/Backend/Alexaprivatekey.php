<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Alexaprivatekey extends Mage_Core_Model_Config_Data
{
    private $_placeholder = '[encrypted]';

    /**
     * Encrypt private key
     */
    public function save()
    {
        $value = trim($this->getValue());

        if ($value != $this->_placeholder) {
            if ($value &&
                strpos($value, 'BEGIN') === false &&
                strpos($$value, 'END') === false
            ) {
                Mage::getSingleton('core/session')->addError(Mage::helper('adminhtml')->__('Please save your Amazon private key in PEM format (-----BEGIN RSA PRIVATE KEY-----)'));
            } else {
                $value = Mage::getModel('core/encryption')->encrypt($this->getValue());
                $this->setValue($value);
                return parent::save();
            }
        }
    }

    /**
     * Set placeholder value
     */
    public function afterLoad()
    {
        if ($this->value) {
            $this->value = $this->_placeholder;
        }
        $this->_afterLoad();
    }
}
