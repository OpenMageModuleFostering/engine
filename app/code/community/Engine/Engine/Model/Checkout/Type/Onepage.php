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
 
class Engine_Engine_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    public function saveBilling($postData, $customerAddressId)
    {
        $isSubscribed = (isset($postData['is_subscribed']) && !empty($postData['is_subscribed']));
        $this->getCheckout()->setCustomerIsSubscribed($isSubscribed);
        
        return parent::saveBilling($postData, $customerAddressId);
    }
}
