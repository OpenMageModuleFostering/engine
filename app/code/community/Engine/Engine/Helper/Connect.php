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

class Engine_Engine_Helper_Connect extends Mage_Core_Helper_Abstract
{
    private $_client  = null;
    private $_mlid    = null;
    private $_confirm = null;
    
    /**
     * Get boolean whether we should confirm subscription
     * @return bool
     */
    private function _getENgineConfirm($storeID=null)
    {
        if (!$this->_confirm) {
            $this->_confirm = (bool) trim(Mage::getStoreConfig('newsletter/confirm', $storeID));
        }
        return $this->_confirm;
    }
    
    /**
     * Get the mailinglistID in which to insert subscriptions.
     * @return int
     */
    private function _getENgineMLID($storeID=null)
    {
        if (!$this->_mlid) {
            $this->_mlid = (int) trim(Mage::getStoreConfig('engine/login/engine_mlid', $storeID));
        }
        return $this->_mlid;
    }

    /**
     * Setup a connection to E-ngine with the configured credentials
     * 
     * @return SoapClient
     */
    public function connectToENgine($storeID=null)
    {
        if (!$this->_client) {
            $engineCustomer = trim(Mage::getStoreConfig('engine/login/engine_customer', $storeID));
            $engineUsername = trim(Mage::getStoreConfig('engine/login/engine_username', $storeID));
            $enginePassword = trim(Mage::getStoreConfig('engine/login/engine_password', $storeID));

            if (
                $engineCustomer!=='' && 
                $engineUsername!=='' && 
                $enginePassword!=='' &&
                $this->_getENgineMLID($storeID) > 0
            ) {        
                try {
                    $loc = "https://" . $engineCustomer . ".e-ngine.nl/soap/server.live.php";
                    $this->_client = new SoapClient(null,
                        array(
                            "location" => $loc,
                            "uri"      => $loc,
                            "login"    => $engineCustomer . "__" . $engineUsername,
                            "password" => $enginePassword,
                            "trace"    => 1
                        )
                    );
                    return $this->_client;
                } catch(Exception $e) {
                    Mage::log("E-ngine: Error connecting to Engine server: ".$e->getMessage());
                }
            }
            return false;
        } else {
            return $this->_client;
        }
    }
    
    /**
     * Subscribe an user (with additonal data) to E-ngine using the provided action ID.
     * This action will be used to send a confirmation e-mail if configured.
     * 
     * @param string $email
     * @param array $data
     * @param int $actionID
     * @return boolean
     */
    public function subscribeENgine($email, array $data, $actionID)
    {
        $client = $this->connectToENgine();

        if ($client !== false) {
            Mage::log("E-ngine: Subscribing new email address: $email");
            try {
                $data['email']   = $email;
                $data['actieid'] = (int) $actionID;
                $result = $client->Subscriber_set($data, $this->_getENgineConfirm(), $this->_getENgineMLID());
                return $result;
            } catch(Exception $e) {
                Mage::log("E-ngine: An error occured: " . $e->getMessage());
            }
        }
        return false;
    }
    
    /**
     * Unsubscribe an emailaddress in E-ngine
     * 
     * @param string $email
     * @return boolean
     */
    public function unsubscribeENgine($email)
    {
        $client = $this->connectToENgine();

        if ($client !== false) {
            Mage::log("E-ngine: Unsubscribing old email address: $email");
            try {
                $result = $client->Subscriber_unsubscribe($email, $this->_getENgineConfirm(), $this->_getENgineMLID());
                return $result;
            } catch(Exception $e) {
                Mage::log("E-ngine: An error occured: " . $e->getMessage());
                return;
            }
        }
        return false;
    }
    
    /**
     * Is E-ngine currently configured?
     * 
     * @return boolean
     */
    public function isENgineSetup($storeID=null)
    {
        $engineCustomer = trim(Mage::getStoreConfig('engine/login/engine_customer', $storeID));
        $engineUsername = trim(Mage::getStoreConfig('engine/login/engine_username', $storeID));
        $enginePassword = trim(Mage::getStoreConfig('engine/login/engine_password', $storeID));
        
        if (
            $engineCustomer!=='' && 
            $engineUsername!=='' && 
            $enginePassword!=='' &&
            $this->_getENgineMLID($storeID) > 0
        ) {
            return true;
        }
        return false;
    }
    
    /**
     * Is E-ngine currently configured to be used to send transactional mails?
     * 
     * @return boolean
     */
    public function isENgineSetupForTransactionalMail($storeID=null)
    {
        $triggersEnabled = trim(Mage::getStoreConfig('engine/mails/engine_enable_trigger', $storeID));
        
        if (
            $this->isENgineSetup($storeID) &&
            $triggersEnabled
        ) {
            return true;
        }
        return false;
    }
    
    public function getENgineTestForTransactionalMail($storeID=null)
    {
        $testEnabled = trim(Mage::getStoreConfig('engine/mails/engine_enable_test', $storeID));
        $testAddress = trim(Mage::getStoreConfig('engine/mails/engine_testaddress', $storeID));
        
        if (
            $this->isENgineSetup($storeID) &&
            $testEnabled &&
            $testAddress != ''
        ) {
            return $testAddress;
        }
        return false;
    }
    
    public function getENgineMailingIDFromContent($content, $title, $subject, $from_name, $from_mail, $reply_mail=null)
    {
        $cacheId = 'engine_template_' . $title;
        $helper = Mage::helper('engine/data');
        $mailingID = $helper->loadCacheData($cacheId);
        if ($mailingID !== false) {
            return $mailingID;
        }
        
        $this->connectToENgine();
        
        $mailingID = $this->_client->Mailing_createFromContent(
            $content,
            '',
            $title,
            $subject,
            $from_name,
            $from_mail,
            $reply_mail,
            $this->_getENgineMLID()
        );
        
        $helper->saveCacheData($mailingID, $cacheId);
        
        return $mailingID;
    }
    
    public function getENgineBounces($from, $till, $storeID=null)
    {
        $this->connectToENgine($storeID);
        
        $bounces = $this->_client->Mailinglist_getUnsubscriptions(
            $from,
            $till,
            array('email'),
            null,
            $this->_getENgineMLID($storeID)
        );
        
        return $bounces;
    }
    
    public function sendMailing($mailingID, $subscribers, $send_date=null)
    {
        $this->connectToENgine();
        
        if (is_null($send_date)) {
            $send_date = date("Y-m-d H:i:s");
        }
        
        $subsMailed = $this->_client->Subscriber_sendMailingToSubscribers($mailingID, $send_date, $subscribers, $this->_getENgineMLID());
        
        return $subsMailed;
    }
    
    
}