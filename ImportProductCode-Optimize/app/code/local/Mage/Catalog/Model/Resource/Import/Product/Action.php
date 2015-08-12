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
 * Catalog Product Mass processing resource model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Resource_Import_Product_Action extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Action
{
    
    /**
     * Mage_Catalog_Model_Resource_Eav_Mysql4_Import_Product_Action::update()
     * 
     * @param mixed $entityId
     * @param mixed $data = array('statics' => , 'attributes' => , 'inventories' => )
     * @param mixed $storeId
     * @return void
     */
    public function update($entityId, $data, $storeId, $obj = null)
    {
        $object = new Varien_Object();
        $object->setIdFieldName('entity_id')
            ->setStoreId($storeId);
            
        $this->_getWriteAdapter()->beginTransaction();
        try {
            
            if($obj == null) $obj = new Varien_Object();
            if(!$obj->getId())
            {
                $this->simpleLoad($obj, $entityId);    
            }
                                    
            //update attributes
            foreach ($data['attributes'] as $attrCode => $value) {
                $attribute = $this->getAttribute($attrCode);
                                
                if(!$this->__checkApply($obj, $attribute))
                {
                    continue;                    
                }

                $object->setId($entityId);
                // collect data for save
                $this->_saveAttributeValue($object, $attribute, $value);
                // save collected data every 1000 rows
            }
            
            //update
            $this->__updateProductValues($entityId, $data['statics']);
            
            //update inventory
            if(count($data['inventories']) > 0)
            {
                $stockItem = Mage::getSingleton('cataloginventory/stock_item');
                $stockItem->setData(array());
                $stockItem->loadByProduct($entityId)
                    ->setProductId($entityId);

                $stockDataChanged = false;
                foreach ($data['inventories'] as $k => $v) {
                    $stockItem->setDataUsingMethod($k, $v);
                    if ($stockItem->dataHasChangedFor($k)) {
                        $stockDataChanged = true;
                    }
                }
                if ($stockDataChanged) {
                    $stockItem->save();
                }
            }
                               
            
            
            $this->_processAttributeValues();
            $this->_getWriteAdapter()->commit();
        } catch (Exception $e) {
            $this->_getWriteAdapter()->rollBack();
            throw $e;
        }
    }
    
    var $__checkApply = array();    
    public function __checkApply($obj, $attribute)
    {
        $attrApply = $attribute->getApplyTo();        
        if(empty($attrApply))
            return true;
        
        $typeId = $obj->getTypeId();
        if(!isset($this->__checkApply[$attribute->getAttributeCode()][$typeId]))
        {
            if(in_array($typeId, $attrApply))
                $this->__checkApply[$attribute->getAttributeCode()][$typeId] = true;
            else
                $this->__checkApply[$attribute->getAttributeCode()][$typeId] = false;                                        
        }                
        return $this->__checkApply[$attribute->getAttributeCode()][$typeId];    
    }            
    
    
    //Update sku, has_options, required_options
    protected function __updateProductValues($entityId , $attrData)
    {
        if(count($attrData) < 1) return $this;
        
        
        $attributeValuesToSave = array();
        $bind = array(
            'entity_id'         => $entityId,
        );
        
        foreach ($attrData as $attrCode => $value) {
            $attribute = $this->getAttribute($attrCode);
            
            //print_r($attribute->getBackendType());
            /*if (!$attribute->getAttributeId() || $attribute->getBackendType() != 'static') {
                continue;
            }*/
            $bind['entity_type_id'] = $attribute->getEntityTypeId();
            $bind[$attrCode] = $this->_prepareValueForSave($value, $attribute);
        }
        
        $table   = $attribute->getBackend()->getTable();
        $attributeValuesToSave[$table][] = $bind;
        
        $adapter = $this->_getWriteAdapter();
        foreach ($attributeValuesToSave as $table => $data) {
            $adapter->insertOnDuplicate($table, $data);
        }

        return $this;
    }
    
    public function simpleLoad($object, $entityId, $attributes = array())
    {
        $select = $this->_getLoadRowSelect($object, $entityId);
        $row = $this->_getReadAdapter()->fetchRow($select);
        //$object->setData($row);
        if (is_array($row)) {
            $object->addData($row);
        }
        
        if(count($attributes)>0)
        {
            $attributeValues = $this->getAttributeValue($entityId, $attributes, 0);
            foreach($attributeValues as $k => $v)
            {
                $object->setDataUsingMethod($k, $v);
            }
        }
    }
    
    
    public function getAttributeValue($entityId, $attribute, $store)
    {
        if (!$entityId || empty($attribute)) {
            return false;
        }
        if (!is_array($attribute)) {
            $attribute = array($attribute);
        }
        $attributesData   = array();
        $staticAttributes = array();
        $typedAttributes  = array();
        $staticTable      = null;
        foreach ($attribute as $_attribute) {
            /* @var $attribute Mage_Catalog_Model_Entity_Attribute */
            $_attribute = $this->getAttribute($_attribute);
            if (!$_attribute) {
                continue;
            }
            $attributeCode = $_attribute->getAttributeCode();
            $attrTable     = $_attribute->getBackend()->getTable();
            $isStatic      = $_attribute->getBackend()->isStatic();
            if ($isStatic) {
                $staticAttributes[] = $attributeCode;
                $staticTable = $attrTable;
            }
            else {
                /**
                 * That structure needed to avoid farther sql joins for getting attribute's code by id
                 */
                $typedAttributes[$attrTable][$_attribute->getId()] = $attributeCode;
            }

        }
        /* @var $select Zend_Db_Select */
        $select = $this->_getReadAdapter()->select();
        /**
         * Collecting static attributes
         */
        if ($staticAttributes) {
            $select->from($staticTable, $staticAttributes)
                ->where($this->getEntityIdField() . ' = ?', $entityId);
            $attributesData = $this->_getReadAdapter()->fetchRow($select);
        }

        /**
         * Collecting typed attributes, performing separate SQL query for each attribute type table
         */
        if ($store instanceof Mage_Core_Model_Store) {
            $store = $store->getId();
        }
        $store = (int)$store;
        if ($typedAttributes) {
            foreach ($typedAttributes as $table => $_attributes) {
                $select->reset()->from(array('default_value' => $table), array());
                $select->where('default_value.attribute_id IN (?)', array_keys($_attributes))
                    ->where('default_value.entity_type_id = ? ', $this->getTypeId())
                    ->where('default_value.entity_id = ? ', $entityId)
                    ->where('default_value.store_id = 0');

                $select->columns(array('default_value.value', 'default_value.attribute_id'));
                
                $result = $this->_getReadAdapter()->fetchAll($select);
                foreach ($result as $key => $_attribute) {
                    $attributeCode = $typedAttributes[$table][$_attribute['attribute_id']];
                    $attributesData[$attributeCode] = $_attribute['value'];
                }
            }
        }

        return $attributesData ? $attributesData : false;
    }
    
    /**
     * Prepare value for save
     *
     * @param mixed $value
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @return mixed
     */
    protected function _prepareValueForSave($value, Mage_Eav_Model_Entity_Attribute_Abstract $attribute)
    {
        $type = $attribute->getBackendType();
        if (($type == 'int' || $type == 'decimal' || $type == 'datetime') && $value === '') {
            return null;
        }
        if ($type == 'decimal') {
            return $this->_getNumber($value);
        }
        return $value;
    }
    
    protected function _getNumber($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_string($value)) {
            return floatval($value);
        }

        //trim space and apos
        $value = str_replace('\'', '', $value);
        $value = str_replace(' ', '', $value);

        $separatorComa = strpos($value, ',');
        $separatorDot  = strpos($value, '.');

        if ($separatorComa !== false && $separatorDot !== false) {
            if ($separatorComa > $separatorDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            else {
                $value = str_replace(',', '', $value);
            }
        }
        elseif ($separatorComa !== false) {
            $value = str_replace(',', '.', $value);
        }
        
        $value = preg_replace('/[^0-9.,]/i', '', $value);
        return floatval($value);
    }
}
