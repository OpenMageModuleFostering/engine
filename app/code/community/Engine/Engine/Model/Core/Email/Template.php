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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Template model
 *
 * Example:
 *
 * // Loading of template
 * $emailTemplate  = Mage::getModel('core/email_template')
 *    ->load(Mage::getStoreConfig('path_to_email_template_id_config'));
 * $variables = array(
 *    'someObject' => Mage::getSingleton('some_model')
 *    'someString' => 'Some string value'
 * );
 * $emailTemplate->send('some@domain.com', 'Name Of User', $variables);
 *
 * @method Mage_Core_Model_Resource_Email_Template _getResource()
 * @method Mage_Core_Model_Resource_Email_Template getResource()
 * @method string getTemplateCode()
 * @method Mage_Core_Model_Email_Template setTemplateCode(string $value)
 * @method string getTemplateText()
 * @method Mage_Core_Model_Email_Template setTemplateText(string $value)
 * @method string getTemplateStyles()
 * @method Mage_Core_Model_Email_Template setTemplateStyles(string $value)
 * @method int getTemplateType()
 * @method Mage_Core_Model_Email_Template setTemplateType(int $value)
 * @method string getTemplateSubject()
 * @method Mage_Core_Model_Email_Template setTemplateSubject(string $value)
 * @method string getTemplateSenderName()
 * @method Mage_Core_Model_Email_Template setTemplateSenderName(string $value)
 * @method string getTemplateSenderEmail()
 * @method Mage_Core_Model_Email_Template setTemplateSenderEmail(string $value)
 * @method string getAddedAt()
 * @method Mage_Core_Model_Email_Template setAddedAt(string $value)
 * @method string getModifiedAt()
 * @method Mage_Core_Model_Email_Template setModifiedAt(string $value)
 * @method string getOrigTemplateCode()
 * @method Mage_Core_Model_Email_Template setOrigTemplateCode(string $value)
 * @method string getOrigTemplateVariables()
 * @method Mage_Core_Model_Email_Template setOrigTemplateVariables(string $value)
 *
 * @category    Mage
 * @package     Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Engine_Engine_Model_Core_Email_Template extends Mage_Core_Model_Email_Template
{
    protected $replyTo = null;
    
    private $_engineReplacements = array();    
    private $_personalizeArray   = array();

    /**
     * Send mail to recipient
     *
     * @param   array|string       $email        E-mail(s)
     * @param   array|string|null  $name         receiver name(s)
     * @param   array              $variables    template variables
     * @return  boolean
     **/
    public function send($email, $name = null, array $variables = array())
    {
        $helper = Mage::helper('engine/connect');
        
		//Should we use E-ngine for transactional emails?
        if (!$helper->isENgineSetupForTransactionalMail()){
            return parent::send($email, $name, $variables);
        }
        $testAddress = $helper->getENgineTestForTransactionalMail();
        
        if ($testAddress !== false) {
            // Send mail via Magento and overwrite email by test address
            parent::send($email, $name, $variables);
            
            $email = array($testAddress);
        }
        
        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array) $email);

        $variables['email'] = reset($emails);

        $this->setUseAbsoluteLinks(true);

        try {
            $connected = $helper->connectToENgine();
                    
            if ($connected) {
                $fromEmail = $this->getSenderEmail();
                $fromName  = $this->getSenderName();

                $data = $this->getTemplateDataForENgine($variables, $this->getTemplateSubject());
                
                $mailingID = $helper->getENgineMailingIDFromContent(
                    $data['html'],
                    'Magento - ' . $this->getTemplateCode(),
                    $data['subject'],
                    $fromName,
                    $fromEmail,
                    $this->replyTo
                );
                
                if (is_numeric($mailingID)) {
                    $subscribers = array();
                    $replace = array();
                    // Replace tags inside tags.
                    foreach ($this->_personalizeArray as $k => $v) {
                        $replace['{{' . $k . '}}'] = $v;
                    }
                    foreach ($this->_personalizeArray as $k => $v) {
                        $this->_personalizeArray[$k] = str_replace(array_keys($replace), $replace, $v);
                    }
                    foreach ($emails as $key => $email) {
                        $this->_personalizeArray['email'] = $email;
                        $subscribers[] = $this->_personalizeArray;
                    }

                    $subsMailed = $helper->sendMailing($mailingID, $subscribers);
                    
                    if ($subsMailed == 0) {
                        return false;
                    }
                } else {
                    return false;
                }
            }

        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;
    }
    
    public function getTemplateDataForENgine($variables, $oldSubject)
    {
        $optionsArray = $this->getVariablesOptionArray();
        
        $engineDelimiter = '--ENGINESEPERATOR--';
        $opts = array();
        foreach ($optionsArray as $v) {
            $opts[] = $v['value'];
        }
        
        $oldText = $this->getTemplateText();
        $oldType = $this->getTemplateType();
        
        if (preg_match_all('/\{\{([^{]*)\}\}/', $oldText.$oldSubject, $matches)) {
            foreach ($matches[0] as $match) {
                if (array_search($match, $opts) === false) {
                    $noBracket = trim($match, '{}');
                    $opts[] = $match;
                    $optionsArray[] = array('value' => $match, 'label' => $noBracket);
                }
            }
        }

        // Parse the generated TXT to retrieve customized variables
        $this->setTemplateType(self::TYPE_TEXT);            
        $this->setTemplateText(implode($engineDelimiter, $opts));
        $generatedTxt = $this->getProcessedTemplate($variables, true);
        $values = explode($engineDelimiter, $generatedTxt);
        $this->setTemplateText($oldText);
        $this->setTemplateType($oldType);

        $this->_personalizeArray   = array();
        $this->_engineReplacements = array();
        foreach ($values as $k => $v) {
            if (isset($optionsArray[$k])) {
                if (!is_array($optionsArray[$k])) {
                    $label = $optionsArray[$k];
                    $value = $optionsArray[$k];
                } else if (isset($optionsArray[$k]['replacement'])) {
                    $label = $optionsArray[$k]['replacement'];
                    $value = $optionsArray[$k]['value'];
                } else {
                    $label = preg_replace('/[^a-z0-9_]/i', '', strtolower(str_replace(' ', '_', $optionsArray[$k]['label'])));
                    $value = $optionsArray[$k]['value'];
                }

                $this->_engineReplacements[$value] = '{{' . $label . '}}';
                $this->_personalizeArray[$label] = str_replace(array("\r", "\n"), ' ', $v);
            }
        }
                
        // Append config variables to be used as replacement tags.
        preg_match_all('/\{\{depend (.*)\}\}(.*)\{\{\/depend\}\}/sU', $oldText.$oldSubject, $matches);
        if (count($matches[0]) > 0) {
            foreach ($matches[1] as $k => $ifMatch)
            {
                $trueValue = '1';
                if ($ifMatch == 'salable') {
                    $trueValue = 'yes';
                } else if(preg_match('/([a-z0-9A-Z]*)\.([a-zA-Z0-9]*)\(\)/', $ifMatch, $match)) {
                    // item exists
                    $tagValue = strtolower($match[1] . '_' . $match[2]);
                    if (isset($variables[$match[1]])) {
                        $object = $variables[$match[1]];
                        $function = $match[2];
                        // Valid method
                        if (method_exists($object, $function)) {
                            $this->_personalizeArray[$tagValue] = str_replace(array("\r", "\n"), ' ', $object->$function());
                        }
                    }
                    $ifMatch = $tagValue;
                }
                
                $trueText = str_replace(array("\r", "\n"), ' ', $matches[2][$k]);
                
                $oldText    = str_replace($matches[0][$k], '{{{if($' . $ifMatch . ' = \'' . $trueValue . '\',[' . $trueText . '],[])}}}', $oldText);
                $oldSubject = str_replace($matches[0][$k], '{{{if($' . $ifMatch . ' = \'' . $trueValue . '\',[' . $trueText . '],[])}}}', $oldSubject);
                
                //$this->_engineReplacements[$matches[0][$k]] = '{{' . $tagValue . '}}';
                //$this->_personalizeArray[$tagValue] = str_replace(array("\r", "\n"), ' ', $configValue);
            }
        }
        
        foreach ($variables as $tagValue => $v) {
            if (!is_object($v) && !is_array($v) && strpos($oldText.$oldSubject, $tagValue)!==false && !isset($this->_personalizeArray[$tagValue])) {
                $this->_personalizeArray[$tagValue] = str_replace(array("\r", "\n"), ' ', $v);
            }
        }

        $this->_engineReplacements[urldecode('%C2%A0')] = ' ';
        
        $engineText = str_replace(array_keys($this->_engineReplacements), $this->_engineReplacements, $oldText);
        $engineSubject = str_replace(array_keys($this->_engineReplacements), $this->_engineReplacements, $oldSubject);

        return array('html' => $engineText, 'subject' => $engineSubject, 'replacements' => $this->_engineReplacements, 'personalize' => $this->_personalizeArray);
    }
    
    public function getTemplateSubjectForENgine($subj=null)
    {
        if (is_null($subj)) {
            $subj = $this->getTemplateSubject();
        }
        return str_replace(array_keys($this->_engineReplacements), $this->_engineReplacements, $subj);
    }

    public function setReplyTo($email) {
		$this->replyTo = $email;
        return parent::setReplyTo($email);
    }
}
