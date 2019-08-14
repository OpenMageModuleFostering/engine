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

class Engine_Engine_Model_Cron
{
    /**
     * Cronjob to process bounces. We unsubscribe all bounces in the last 24h in Magento.
     */
    public function processBounces()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $storeID) {
            $helper = Mage::helper('engine/connect');
            if ($helper->isENgineSetup($storeID)) {
                $this->_processBounces($storeID);
            }
        }
    }

    /**
     * Process bounces for a specific store
     * 
     * @param $storeID
     */
    protected function _processBounces($storeID)
    {
        $helper = Mage::helper('engine/connect');
        
        $from = date("Y-m-d 00:00:00", strtotime('yesterday'));
        $till = date("Y-m-d 23:59:59", strtotime('yesterday'));
        
        $bounces = $helper->getENgineBounces($from, $till, $storeID);
        foreach ($bounces as $bounce)
        {
            if (isset($bounce['email']) && $bounce['email']!='') {
                $subscriber = Mage::getModel('newsletter/subscriber')
                    ->loadByEmail($bounce['email']);

                // Did we find an valid subscriber?
                if (!is_null($subscriber->getSubscriberEmail())) {
                    // Don't push to E-ngine
                    $subscriber->unsubscribe(false);
                }
            }
        }
        
    }
}
