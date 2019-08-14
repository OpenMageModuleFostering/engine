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
 
class Engine_Engine_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    /**
     * This function subscribes an emailadress in the Magento DB and also in the E-ngine DB
     * 
     * - Newsletter signup box on homepage
     * 
     * @param string $email
     * @param bool $pushToENgine
     * @return int
     */
    public function subscribe($email, $pushToENgine=true)
    {
        $isEnabled = Mage::getStoreConfig('engine/actions/engine_syncsubscribers');
        if ($pushToENgine && $isEnabled) {
            $helper = Mage::helper('engine/connect');
            $actionID = (int) trim(Mage::getStoreConfig('engine/actions/engine_actionid_signup'));
            
            $data['campaign'] = 'subscribe_form';
            $data['source']   = 'website';
                
            $helper->subscribeENgine($email, $data, $actionID);
        }
        
        return parent::subscribe($email);
    }
    
    /**
     * This function unsubscribes an emailadress in the Magento DB and also in the E-ngine DB
     * 
     * - Admin mass delete / unsubscribe
     * 
     * @param bool $pushToENgine
     * @return int
     */
    public function unsubscribe($pushToENgine=true)
    {
        $isEnabled = Mage::getStoreConfig('engine/actions/engine_syncsubscribers');
        if ($pushToENgine && $isEnabled) {
            $helper = Mage::helper('engine/connect');
            $helper->unsubscribeENgine($this->getSubscriberEmail());
        }

        return parent::unsubscribe();
    }

    /**
     * Prevent sending of an Magento confirmation mail if E-ngine is setup correctly.
     * @return boolean
     */
    public function sendConfirmationSuccessEmail()
    {
        $isEnabled = Mage::getStoreConfig('engine/actions/engine_syncsubscribers');
        $helper = Mage::helper('engine/connect');
        // ENgine setup? Then let E-ngine send confirmation mails based on actionID
    	if ($isEnabled && $helper->isENgineSetup()) {
        	return $this;
    	} else {
    		return parent::sendConfirmationSuccessEmail();
    	}
    }

    /**
     * Prevent sending of an Magento confirmation mail if E-ngine is setup correctly.
     * @return boolean
     */
    public function sendUnsubscriptionEmail()
    {
        $isEnabled = Mage::getStoreConfig('engine/actions/engine_syncsubscribers');
        $helper = Mage::helper('engine/connect');
        // ENgine setup? Then let E-ngine send confirmation mails based on actionID
    	if ($isEnabled && $helper->isENgineSetup()) {
        	return $this;
    	} else {
    		return parent::sendUnsubscriptionEmail();
    	}
    }
	
}
