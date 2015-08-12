<?php
/**
* @copyright Copyright (c) 2012 MagentoWhiz.
* @package MW_AdvancedInvoicePrinting
*/
class MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Position{
    const TOP   = 'TOP';
    const BOTTOM = 'BOTTOM';

    public function toOptionArray()
    {
        return array(
            array('value' => 'TOP', 'label'=>Mage::helper('adminhtml')->__('TOP')),            
            array('value' => 'BOTTOM', 'label'=>Mage::helper('adminhtml')->__('BOTTOM')),
        );
    }
}
