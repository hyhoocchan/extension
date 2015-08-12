<?php

class Mage_Catalog_Model_Convert_Adapter_Extend_UpdateInventory extends
Mage_Catalog_Model_Convert_Adapter_ProductUpdate {

    var $__defaultAttributes = array();
    var $__vendorName = '';
    var $__skuFieldName = 'sku';
    var $__isUpdate = true;
    //TO DO : vendor_sku field have strange character
    var $__ftp = null;

    public function saveRow(array $importData, $keepImage = true) {
        $this->__dataBeSaved = $this->__defaultAttributes;
        if (!$this->checkBeforeSave($importData)) {
            return;
        }

        
		//$importData['color'] = strtolower($importData['color']);
		//var_dump($importData['color']);
		$importData['color'] = $this->getOptionIdbyLabel('scheuerlinen_color',strtolower($importData['color']));
        $importData['size'] = $this->getOptionIdbyLabel('scheuerlinen_size',$importData['size']);
        //var_dump($importData['color']);
		$this->doDataFilter($importData);
		
        $this->__dataBeSaved = array_merge($this->__dataBeSaved, array(
            'sku' => $importData[$this->__skuFieldName]
                ));

        return parent::saveRow($this->__dataBeSaved, true);
    }

    protected function doDataFilter($data) {

        parent::doDataFilter($data);
        $this->convertData($data, $this->__dataBeSaved, array(
        	'manufacturer' => 'vendor',
        	'price' => 'retail',
            'special_price' => 'sale price',
        	'scheuerlinen_size' => 'size',
            'scheuerlinen_color' => 'color',
            'shipping_lead_time' => 'shipping lead time'
        ));

        //$this->__dataBeSaved['is_in_stock'] = '1';
        return $this->__dataBeSaved;
    }
    
    
    private function getOptionIdbyLabel($attributeCode,$label) {
        $sql = "select  eav_attribute_option.option_id	
from eav_attribute as a 
inner join eav_attribute_option on eav_attribute_option.attribute_id = a.attribute_id
inner join eav_attribute_option_value on eav_attribute_option_value.option_id = eav_attribute_option.option_id
where a.attribute_code = '$attributeCode' 
and eav_attribute_option_value.value = '$label'";
        
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $id = $read->fetchOne($sql);
        if ($id)
            return $id;
        return false;
    }

}
