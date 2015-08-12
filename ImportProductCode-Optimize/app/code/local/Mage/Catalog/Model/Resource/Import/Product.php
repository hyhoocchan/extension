<?php

class Mage_Catalog_Model_Resource_Import_Product extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product
{
    public function saveCategories(Varien_Object $object)
    {
        $this->_saveCategories($object);
        return $this;
    }
    
    /**
     * Save product category relations
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product
     */
    public function removeCategories(Varien_Object $object)
    {
        $write = $this->_getWriteAdapter();

        $write->delete($this->_productCategoryTable, $write->quoteInto('product_id=?', $object->getId()));


/*        if (!empty($insert) || !empty($delete)) {
            $object->setAffectedCategoryIds(array_merge($insert, $delete));
            $object->setIsChangedCategories(true);
        }*/

        return $this;
    }
}

?>