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
class Engine_Engine_Block_Extrafields extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_magentoFields;

    public function __construct()
    {
        $this->addColumn('magentoFieldName', array(
            'label' => Mage::helper('engine')->__('Magento field name'),
            'size'  => 30,
        ));
        $this->addColumn('engineFieldName', array(
            'label' => Mage::helper('engine')->__('E-ngine field name'),
            'size'  => 30
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('engine')->__('Add extra field');
        
        parent::__construct();
        $this->setTemplate('engine/field/extra_field.phtml');
        
        // customer options
        $magentoAttributes = Mage::getModel('customer/customer')->getAttributes();
        $this->_magentoFields = array();
        $invalidAttr = array(
            'updated_at', 'created_at', 'email',
            'default_billing', 'default_shipping',
            'entity_type_id', 'entity_id',
            'attribute_set_id', 'password_hash', 'increment_id'
        );
        foreach(array_keys($magentoAttributes) as $attr) {
            if(array_search($attr, $invalidAttr) === false) {
                $this->_magentoFields[$attr] = $attr;
            }
        }
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception(Mage::helper('engine')->__('Wrong column name specified.'));
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($columnName == 'magentoFieldName'){
            asort($this->_magentoFields);
            
            $rendered = '<select name="'.$inputName.'">';
            foreach ($this->_magentoFields as $att => $name) {
                $rendered .= '<option value="' . $att . '">' . $name . '</option>';
            }
            $rendered .= '</select>';        
            
            return $rendered;
        } else {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
                ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
                (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
                (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
        }
    }
}
