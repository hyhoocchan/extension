<?php
/**
 * Magento Commercial Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Commercial Edition License
 * that is available at: http://www.magentocommerce.com/license/commercial-edition
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
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/commercial-edition
 */


/**
 * Catalog Product Mass Action processing model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Import_Product_Action extends Mage_Catalog_Model_Product_Action
{
    var $__inventoryAttributeCode = array('is_in_stock', 'qty');
    
    
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/import_product_action');
    }

    /**
     * Update attribute values for entity list per store
     *
     * @param array $productIds
     * @param array $attrData
     * @param int $storeId
     * @return Mage_Catalog_Model_Product_Action
     */
    public function updateAttributes($productIds, $attrData, $storeId)
    {
        $this->_getResource()->updateAttributes($productIds, $attrData, $storeId);
        $this->setData(array(
            'product_ids'       => array_unique($productIds),
            'attributes_data'   => $attrData,
            'store_id'          => $storeId
        ));

        // register mass action indexer event
        /*Mage::getSingleton('index/indexer')->processEntityAction(
            $this, Mage_Catalog_Model_Product::ENTITY, Mage_Index_Model_Event::TYPE_MASS_ACTION
        );*/
        return $this;
    }
    
    public function getAttributes($productId, $attribute, $storeId)
    {
        return $this->_getResource()->getAttributeRawValue($productId, $attribute, $storeId);
    }
    
    public function updateProduct($productId, $attrData)
    {
        $this->_getResource()->updateProductValues($productId, $attrData);
    }
    
    public function update($productId, $data, $storeId, $obj = null)
    {
        $this->_getResource()->update($productId, $data, $storeId, $obj);
    }
    
    protected function __getUseAttributeList($importData)
    {
        if(!isset($this->__useAttributeList))
       {
            $attrList = array('attributes' => array(), 'statics' => array());
            //$attribute = $this->_getResource()->getAttribute($attributeCode);
            foreach($importData as $attrCode => $value) {
                $attribute = $this->_getResource()->getAttribute($attrCode);
                
                if ($attribute && $attribute->getAttributeId()) {
                    if($this->_getResource()->isAttributeStatic($attribute))
                        $attrList['statics'][$attrCode] = $attribute->getAttributeId();
                    else
                        $attrList['attributes'][$attrCode] = $attribute->getAttributeId();
                }
            }
            
            $this->__useAttributeList = $attrList;
        }
        return $this->__useAttributeList;
    }
    
    protected function __getUseInventoryList($importData)
    {
        if(!isset($this->__useInventoryList))
        {
            $attrList = array();
            
            foreach ($this->__inventoryAttributeCode as $attributeCode) {
                if (array_key_exists($attributeCode, $importData)) {
                    $attrList[$attributeCode] = $attributeCode;
                }
            }
            
            $this->__useInventoryList = $attrList;
        }
        return $this->__useInventoryList;
    }
    
    public function getAtrributeData($importData)
    {
        $attrList = $this->__getUseAttributeList($importData);
        $inventoryList = $this->__getUseInventoryList($importData);                   
        $attrData = array('attributes' => array(), 'statics' => array(), 'inventories'=>array());
        //print_r($attrList);
        foreach($attrList['attributes'] as $attrCode => $attribute)
        {
            if(array_key_exists($attrCode, $importData))
            {
                $attrData['attributes'][$attrCode] = $importData[$attrCode];
            }
        }
        foreach($attrList['statics'] as $attrCode => $attribute)
        {
            if(array_key_exists($attrCode, $importData))
            {
                $attrData['statics'][$attrCode] = $importData[$attrCode];
            }
        }
        foreach($inventoryList as $attrCode)
        {
            if(array_key_exists($attrCode, $importData));
                $attrData['inventories'][$attrCode] = $importData[$attrCode];
        }
        
        return $attrData;
    }
    
    
    /**
     * Mage_Catalog_Model_Import_Product_Action::simpleLoad()
     * 
     * @param mixed $data = array('attributes', 'inventories')
     * @return void
     */
    public function simpleLoad($data, $entityId)
    {
        $object = new Varien_Object();
        $this->_getResource()->simpleLoad($object, $entityId, $data['attributes']);
        
        if(count($data['inventories']) > 0)
        {
            $stockItem = Mage::getSingleton('cataloginventory/stock_item');
            $stockItem->setData(array());
            $stockItem->loadByProduct($entityId)
                ->setProductId($entityId);
                
            foreach($data['inventories'] as $attr => $vv)
            {
                $object->setData($attr, $stockItem->getData($attr));
            }
        }
        
        return $object;
    }
    
    
    public function simpleLoadProduct($data, $product)
    {
        $this->_getResource()->simpleLoad($product, $product->getId(), $data['attributes']);
        
        if(count($data['inventories']) > 0)
        {
            $stockItem = Mage::getSingleton('cataloginventory/stock_item');
            $stockItem->setData(array());
            $stockItem->loadByProduct($product->getId());
                
            foreach($data['inventories'] as $attr => $vv)
            {
                $product->setData($attr, $stockItem->getData($attr));
            }
        }
        return $product;
    }
}
