<?php
/**
* @copyright Copyright (c) 2012 MagentoWhiz.
* @package MW_AdvancedInvoicePrinting
*/
class MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Alignment{
    const LEFT   = 'LEFT';
    const CENTER = 'CENTER';
    const RIGHT  = 'RIGHT';

    public function toOptionArray()
    {
        return array(
            array('value' => 'LEFT', 'label'=>Mage::helper('adminhtml')->__('LEFT')),            
            array('value' => 'CENTER', 'label'=>Mage::helper('adminhtml')->__('CENTER')),
            array('value' => 'RIGHT', 'label'=>Mage::helper('adminhtml')->__('RIGHT')),
        );
    }
}
