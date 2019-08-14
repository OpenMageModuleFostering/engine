<?php
class Engine_Engine_Block_Newsletter extends Mage_Checkout_Block_Onepage_Abstract
{
    /**
     * Allow using of E-ngine / newsletter block to be used when allowing signup from order form.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('engine/newsletter.phtml');
    }
}