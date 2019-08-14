<?php
class Engine_Engine_Block_Ecommerce extends Mage_Core_Block_Template
{
    public $_order;

    public function isEcommerce()
    {
        $successPath =  Mage::getStoreConfig('engine/ecommerce/success_url') != "" ? Mage::getStoreConfig('engine/ecommerce/success_url') : '/checkout/onepage/success';

        if($this->getParentBlock()->getNameInLayout() == 'before_body_end'
            && Mage::getStoreConfigFlag('engine/ecommerce/engine_marketing')
            && strpos($this->getRequest()->getPathInfo(), $successPath) !== false
            ){
                return true;
        }
        return false;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if(!isset($this->_order)){
            $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
            $this->_order = Mage::getModel('sales/order')->load($orderId);
        }
        return $this->_order;
    }

    public function getTransactionIdField()
    {
        return Mage::getStoreConfig('engine/ecommerce/transaction_id') != false ? Mage::getStoreConfig('engine/ecommerce/transaction_id') : 'entity_id';
    }

    public function getENgineID()
    {
        return Mage::getStoreConfig('engine/ecommerce/engine_id');
    }

    public function getENgineSubdomain()
    {
        return Mage::getStoreConfig('engine/ecommerce/engine_subdomain');
    }

    public function getENgineCustomer()
    {
        return Mage::getStoreConfig('engine/login/engine_customer');
    }

    public function getIncludeShipping()
    {
        return (bool) Mage::getStoreConfig('engine/ecommerce/engine_include_shipping');
    }

    public function getIncludeTax()
    {
        return (bool) Mage::getStoreConfig('engine/ecommerce/engine_include_tax');
    }
}