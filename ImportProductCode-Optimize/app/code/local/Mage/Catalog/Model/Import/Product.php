<?php
class Mage_Catalog_Model_Import_Product extends Mage_Catalog_Model_Product
{
    //protected $_eventPrefix      = 'catalog_product___';
    
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/import_product');
    }

    public function cleanCache()
    {
        //Mage::app()->cleanCache('catalog_product_'.$this->getId());
        return $this;
    }
    
    public function cleanModelCache()
    {
        return $this;
    }
}