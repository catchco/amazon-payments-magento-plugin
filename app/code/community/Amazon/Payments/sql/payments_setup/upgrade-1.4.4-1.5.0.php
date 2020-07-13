<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */


$installer = $this;

$installer->startSetup();

$db = $installer->getConnection();

// Rename "enabled" path to "active"
$db->update(
    Mage::getSingleton('core/resource')->getTableName('core_config_data'),
    array('path' => 'payment/amazon_payments/active'),
    array('path = ?' => 'payment/amazon_payments/enabled')
);

$installer->endSetup();