<?php 
class Importpro_Catalog_Model_Product_Type_Configurable extends Mage_Catalog_Model_Product_Type_Configurable
{
    /**
     * Declare attribute identifiers used for asign subproducts
     *
     * @param   array $ids
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Catalog_Model_Product_Type_Configurable
     */
    public function setUsedProductAttributeIds($ids, $product = null)
    {
        $usedProductAttributes  = array();
        $configurableAttributes = array();

        foreach ($ids as $attributeId) {
            $usedProductAttributes[]  = $this->getAttributeById($attributeId, $product);
            $configurableAttributes[] = Mage::getModel('catalog/product_type_configurable_attribute')
                ->setProductAttribute($this->getAttributeById($attributeId, $product));
        }
        $this->getProduct($product)->setData($this->_usedProductAttributes, $usedProductAttributes);
        $this->getProduct($product)->setData($this->_usedProductAttributeIds, $ids);
        $this->getProduct($product)->setData($this->_configurableAttributes, $configurableAttributes);

        return $this;
    }
}
