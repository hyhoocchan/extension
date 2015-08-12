<?php
class Mage_Catalog_Model_Import_Category extends Mage_Catalog_Model_Category
{
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