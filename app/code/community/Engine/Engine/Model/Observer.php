<?php
/**
 * E-ngine Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   Engine
 * @package    Engine_Engine
 * @author     Michiel van de Vis
 * @copyright  Copyright (c) 2014 Engine BV (www.e-ngine.nl)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Engine_Engine_Model_Observer
{    
    /**
     * This function checks the currect subscription status for a customer and subscribes it when necessary.
     * 
     * - Subscribe from user profile
     * - Subscribe as customer when ordering
     * 
     * @param type $observer
     * @return void
     */
    public function checkSubscriptionStatus($observer)
    {
        $event      = $observer->getEvent();
        $customer   = $event->getCustomer();

        $newEmail   = $customer->getEmail();
        $subscribed = $customer->getIsSubscribed();
        
        $oldEmail = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();
        // if subscribed is NULL (i.e. because the form didn't set it one way
        // or the other), get the existing value from the database
        if ($subscribed === null) {
            $subscribed = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed();
        }

        try {
            $helper = Mage::helper('engine/connect');
            if (!$subscribed || ($oldEmail && $newEmail != $oldEmail)) {
                // Not subscribed or changing email? Unsubscribe old email address
                $result = $helper->unsubscribeENgine($oldEmail);
            } else if ($subscribed) {
                // Should we subscribe the customer?
                $data = Engine_Engine_Model_Observer::generateCustomFields($customer);
                $actionID = (int) trim(Mage::getStoreConfig('engine/actions/engine_actionid_account'));
                
                $data['campaign'] = 'subscribe_account';
                $data['source']   = 'website';

                $result = $helper->subscribeENgine($newEmail, $data, $actionID);
            }
            return $result;
        } catch(Exception $e) {
            Mage::log("E-ngine: An error occured: ".$e->getMessage());
        }
        return;
    }

    /**
     * This function deletes/unsubscribed an customer.
     * 
     * - Unsubscribe from user profile
     * 
     * @param type $observer
     * @return void
     */
    public function customerDeleted($observer)
    {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();

        $email = $customer->getEmail();

        $helper = Mage::helper('engine/connect');

        Mage::log("E-ngine: Customer deleted, unsubscribing: $email");
        try {
            $result = $helper->unsubscribeENgine($email);
            return $result;
        } catch(Exception $e) {
            Mage::log("E-ngine: An error occured: ".$e->getMessage());
        }
        return;
    }
    
    /**
     * Store subscription state in checkout object
     * 
     * - Allow signup while ordering
     * 
     * @param type $observer
     */
    public function setCustomerIsSubscribed($observer)
    {
        if ((bool) Mage::getSingleton('checkout/session')->getCustomerIsSubscribed()) {
            $quote = $observer->getEvent()->getQuote();
            $customer = $quote->getCustomer();
            switch ($quote->getCheckoutMethod()) {
                case Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER:
				case Mage_Sales_Model_Quote::CHECKOUT_METHOD_LOGIN_IN:
					$customer->setIsSubscribed(1);
                        
                    // Also subscribe in Magento
                    Mage::getModel('newsletter/subscriber')->subscribe($customer->getEmail(), false);
					break;
                case Mage_Sales_Model_Quote::CHECKOUT_METHOD_GUEST:
                    $session = Mage::getSingleton('core/session');
                    try {
                        $data     = Engine_Engine_Model_Observer::generateCustomFields($quote->getBillingAddress());
                        $email    = $quote->getBillingAddress()->getEmail();
                        $actionID = (int) trim(Mage::getStoreConfig('engine/actions/engine_actionid_order'));
                        
                        $data['campaign'] = 'subscribe_order';
                        $data['source']   = 'website';

                        $helper = Mage::helper('engine/connect');
                        $status = $helper->subscribeENgine($email, $data, $actionID);
                        if ($status == 'OK_CONFIRM'){
                            $session->addSuccess(Mage::helper('engine')->__('You will receive a confirmation email to confirm your subscription.'));
                        }
                        
                        // Also subscribe in Magento
                        Mage::getModel('newsletter/subscriber')->subscribe($quote->getBillingAddress()->getEmail(), false);
                    } catch (Mage_Core_Exception $e) {
                        $session->addException($e, Mage::helper('engine')->__('There was a problem with the newsletter subscription: %s', $e->getMessage()));
                    } catch (Exception $e) {
                        $session->addException($e, Mage::helper('engine')->__('There was a problem with the newsletter subscription'));
                    }
                    break;
            }
            
            Mage::getSingleton('checkout/session')->setCustomerIsSubscribed(0);
        }
    }
    
    
    
    // get array of linked attributes from the config settings and
    // populate it
    public static function generateCustomFields($customer)
    {
        $attrConversion = @unserialize(
            Mage::getStoreConfig('engine/actions/engine_extrafields', Mage::app()->getStore()->getStoreId())
        );
        $customFields = array();
        if (count($attrConversion) > 0) {
            $customerData = $customer->getData();
            foreach ($attrConversion as $fieldConvert) {
                $magentoAtt  = $fieldConvert['magentoFieldName'];
                $translateTo = $fieldConvert['engineFieldName'];
                
                 if (isset($customerData[$magentoAtt])) {
                    $customFields[$translateTo] = self::convertColumn($customer, $magentoAtt, $customerData[$magentoAtt]);
                }
            }
        }

        return $customFields;
    }
    
    /**
     * Convert column value to E-ngine can manage it.
     * @param type $name
     * @param type $value
     * @return string
     */
    public static function convertColumn($customer, $name, $value)
    {
        switch ($name) {
            case 'gender':
                if ($value === '1') {
                    return 'm';
                } else if ($value === '2') {
                    return 'f';
                }
                return 'u';
            case 'dob':
                return date("Y-m-d", strtotime($value));
            case 'website_id':
                return self::retrieveValueFromModel($customer->getWebsiteId(), 'core/website', 'name', $value);
            case 'store_id':
                return self::retrieveValueFromModel($customer->getStoreId(), 'core/store', 'name', $value);
            case 'group_id':
                return self::retrieveValueFromModel($customer->getGroupId(), 'customer/group', 'customer_group_code', $value);
        }
        return $value;
    }
    
    /**
     * Retrieve a column from a model
     * 
     * @param int $siteID
     * @param string $model
     * @param string $column
     * @return string value
     */
    public static function retrieveValueFromModel($modelID, $model, $column, $default)
    {
        $d = Mage::getModel($model)->load($modelID)->getData();
        if (isset($d[$column])) {
            return $d[$column];
        }
        return $default;
    }
}
