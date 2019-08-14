<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Engine
 * @package    Engine_Engine
 * @author     Michiel van de Vis
 * @copyright  Copyright (c) 2014 Engine BV (www.e-ngine.nl)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
include_once('Mage/Adminhtml/controllers/System/Email/TemplateController.php');

class Engine_Engine_Admin_System_Email_TemplateController extends Mage_Adminhtml_System_Email_TemplateController
{
    /**
     * Invalidate cache after saving template
     */
    public function saveAction()
    {
        $helper = Mage::helper('engine/data');
        $helper->invalidateCache();

        // redirect to parent action
        parent::saveAction();
    }
}
