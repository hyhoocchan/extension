<?php

/**
 * Product_import.php
 * CommerceThemes @ InterSEC Solutions LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.commercethemes.com/LICENSE-M1.txt
 *
 * @category   Product
 * @package    Productimport
 * @copyright  Copyright (c) 2003-2009 CommerceThemes @ InterSEC Solutions LLC. (http://www.commercethemes.com)
 * @license    http://www.commercethemes.com/LICENSE-M1.txt
 */
class Mage_Catalog_Model_Convert_Adapter_Productimport extends
Mage_Catalog_Model_Convert_Adapter_Product {

    var $__globalCatalogName = 'Category';
    var $__listFtpImages = array();
    var $__fileAfterImport = null;
    var $__isUpdate = false;
    var $__current_position = 0;
    var $___productConfModel = null;
    var $__defaultUpdateAttributes = array(
        'store' => 'admin',
        'store_id' => '0',
        'attribute_set' => 'Default',
        'type' => 'simple'
    );
    var $__defaultMasterAttributes = array(
        'store' => 'admin',
        'store_id' => '0',
        'attribute_set' => 'Default',
        'type' => 'simple',
        'has_options' => '0',
        'status' => 'Enabled',
        'tax_class_id' => 'None',
        'visibility' => 'Catalog, Search',
        'enable_googlecheckout' => '0',
        'disable_googlecheckout' => '1',
        'backorders' => '0',
        'use_config_backorders' => '1',
        'min_sale_qty' => '1',
        'use_config_min_sale_qty' => '1',
        'max_sale_qty' => '0',
        'use_config_max_sale_qty' => '1',
        'is_qty_decimal' => '0',
        'use_config_min_qty' => '1',
        'is_in_stock' => '1',
        'low_stock_date' => '',
        'notify_stock_qty' => '',
        'use_config_notify_stock_qty' => '1',
        'manage_stock' => '1',
        'use_config_manage_stock' => '1',
        'stock_status_changed_automatically' => '0',
        'use_config_qty_increments' => '1',
        'qty_increments' => '0',
        'use_config_enable_qty_increments' => '1',
        'enable_qty_increments' => '0',
        'product_type_id' => 'simple',
        'product_status_changed' => '',
        'product_changed_websites' => '',
        //extra attributes
        'is_on_sale' => 0,
        'featured' => 0,
            //'salable'           => 1,
            //'buy_text'        => '',
            /*
              "meta_title"
              "meta_description"
              "image"
              "thumbnail"
              "gift_message_available"
              "custom_design"
              "options_container"
              "minimal_price"
              "meta_keyword",
              "custom_layout_update"


              "tier_prices"
              "associated"
              "grouped"
              "image_label"
              "small_image_label"
              "thumbnail_label"
              ""
              "special_price"
              "special_from_date","gallery"
              "related"
              "upsell"
              "crosssell"

              "category_ids"      => ''
              'has_options'       => '1',
              'name'              => '',
              'manufacturer'      => '',
              'price'             => '0',
              'cost'              => '',
              'special_price'     => '',
              'meta_title'        => '',
              'meta_description'  => '',
              'config_attributes' => '',
              'custom_design'     => '',
              'options_container' => 'Block after Info Column',
              'page_layout'       => 'No layout updates.',
              'samples_title'     => 'Samples',
              'links_title'       => 'Download',
              'thumbnail_label'   => '',
              'small_image_label' => '',
              'image_label'       => '',
              'gallery'           => '',
              'meta_keyword'      => '',
              'custom_layout_update' => '', */

            /* 'links_purchased_separately' => '1',
              'special_from_date' => '',
              'special_to_date'   => '',
              'custom_design_from' => '',
              'custom_design_to'  => '',
              'news_from_date'    => '',
              'news_to_date'      => '', */

            //'related'       => '',
            //'upsell'        => '',
            //'crosssell'     => '',
            //'tier_prices'   => '',
            //'associated'    => '',
            //'bundle_options' => '',
            //'grouped'       => '',
            //'super_attribute_pricing' => '',
            //'product_tags'  => '',
            //'links_exist'   => '1',
    );

    /**
     * Initialize convert adapter model for products collection
     *
     */
    public function __construct() {
        $fieldset = Mage::getConfig()->getFieldset('catalog_product_dataflow', 'admin');
        foreach ($fieldset as $code => $node) {
            /* @var $node Mage_Core_Model_Config_Element */
            if ($node->is('inventory')) {
                foreach ($node->product_type->children() as $productType) {
                    $productType = $productType->getName();
                    $this->_inventoryFieldsProductTypes[$productType][] = $code;
                    if ($node->is('use_config')) {
                        $this->_inventoryFieldsProductTypes[$productType][] = 'use_config_' . $code;
                    }
                }

                $this->_inventoryFields[] = $code;
                if ($node->is('use_config')) {
                    $this->_inventoryFields[] = 'use_config_' . $code;
                }
            }
            if ($node->is('required')) {
                $this->_requiredFields[] = $code;
            }
            if ($node->is('ignore')) {
                $this->_ignoreFields[] = $code;
            }
            if ($node->is('img')) {
                $this->_imageFields[] = $code;
            }
            if ($node->is('to_number')) {
                $this->_toNumber[] = $code;
            }
        }

        $this->setVar('entity_type', 'catalog/product');
        if (!Mage::registry('Object_Cache_Product')) {
            $this->setProduct(Mage::getModel('catalog/product'));
        }

        if (!Mage::registry('Object_Cache_StockItem')) {
            $this->setStockItem(Mage::getModel('cataloginventory/stock_item'));
        }

        $this->__globalCatalogName = Mage::getStoreConfig('catalog/catagory/global_category_name');

        $area = array('global', 'frontend', 'adminhtml');
        foreach ($area as $ar) {
            $aa = Mage::getConfig()->getEventConfig($ar, 'catalog_product_save_after');
            if (isset($aa->observers)) {
                foreach ($aa->observers as $observers) {
                    unset($observers->destroy_product_cache_objects, $observers->catalogsearch, $observers->googleoptimizer_observer, $observers->googlebase_observer);
                }
            }
            $aa = Mage::getConfig()->getEventConfig($ar, 'catalog_product_delete_after');
            if (isset($aa->observers)) {
                foreach ($aa->observers as $observers) {
                    unset($observers->catalogsearch);
                }
            }
        }
    }

    /**
     * Save product (import)
     *
     * @param array $importData
     * @throws Mage_Core_Exception
     * @return bool
     */
    public function saveRow(array $importData, $keepImage = false) {
        return $this->insertRow($importData, $keepImage);
    }

    public function insertRow(array $importData, $keepImage = false) {
        //print_r(date('i:s:u') . '______1' . "\n");
        $product = $this->getProductModel()->reset();

        $rowInfo = (empty($importData['name']) ? '' : $importData['name']) . (empty($importData['sku']) ? ' ' : "[{$importData['sku']}] ");

        if (empty($importData['store'])) {
            if (!is_null($this->getBatchParams('store'))) {
                $store = $this->getStoreById($this->getBatchParams('store'));
            } else {
                $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" not defined', 'store');
                $this->logErrors($message);
                Mage::throwException($message);
            }
        } else {
            $store = $this->getStoreByCode($importData['store']);
        }

        if ($store === false) {
            $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, store "%s" field not exists', $importData['store']);
            $this->logErrors($message);
            Mage::throwException($message);
        }

        if (empty($importData['sku'])) {
            $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" not defined', 'sku');
            $this->logErrors($message);
            Mage::throwException($message);
        }
        if (isset($importData['name']) && empty($importData['name'])) {
            $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" not defined', 'name');
            $this->logErrors($message);
            Mage::throwException($message);
        }
        $importData['sku'] = substr($importData['sku'], 0, 64);
        $product->setStoreId($store->getId());
        $productId = $product->getIdBySku($importData['sku']);
//print_r(date('i:s:u') . '______4' . "\n");
        $new = true; // fix for duplicating attributes error
        if ($productId) {
            $product->load($productId);
            $new = false; // fix for duplicating attributes error
        } else {
            if ($this->__isUpdate)
                return;
            $productTypes = $this->getProductTypes();
            $productAttributeSets = $this->getProductAttributeSets();

            /**
             * Check product define type
             */
            if (empty($importData['type']) || !isset($productTypes[strtolower($importData['type'])])) {
                $value = isset($importData['type']) ? $importData['type'] : '';
                $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, is not valid value "%s" for field "%s"', $value, 'type');
                $this->logErrors($message);
                Mage::throwException($message);
            }
            $product->setTypeId($productTypes[strtolower($importData['type'])]);
            /**
             * Check product define attribute set
             */
            if (empty($importData['attribute_set']) || !isset($productAttributeSets[$importData['attribute_set']])) {
                $value = isset($importData['attribute_set']) ? $importData['attribute_set'] : '';
                $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, is not valid value "%s" for field "%s"', $value, 'attribute_set');
                $this->logErrors($message);
                Mage::throwException($message);
            }
            $product->setAttributeSetId($productAttributeSets[$importData['attribute_set']]);

            foreach ($this->_requiredFields as $field) {
                $attribute = $this->getAttribute($field);
                if (!isset($importData[$field]) && $attribute && $attribute->getIsRequired()) {
                    $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" for new products not defined', $field);
                    $this->logErrors($message);
                    Mage::throwException($message);
                }
            }
        }


        $iscustomoptions = "false"; //sets currentcustomoptionstofalse
        $finalsuperattributepricing = "";
        $finalsuperattributetype = $product->getTypeId();
        if (isset($importData['super_attribute_pricing'])) {
            $finalsuperattributepricing = $importData['super_attribute_pricing'];
        }
//print_r($product->getTypeInstance(true));

        $this->setProductTypeInstance($product);
//print_r(date('i:s:u') . '______5' . "\n");        
//print_r(array_keys($this->_productTypeInstances));
        // delete disabled products
        if ($importData['status'] == 'Deleted'/* Disabled */) {
            $product = Mage::getSingleton('catalog/product')->load($productId);
            $this->_removeFile(Mage::getSingleton('catalog/product_media_config')->
                            getMediaPath($product->getData('image')));
            $this->_removeFile(Mage::getSingleton('catalog/product_media_config')->
                            getMediaPath($product->getData('small_image')));
            $this->_removeFile(Mage::getSingleton('catalog/product_media_config')->
                            getMediaPath($product->getData('thumbnail')));
            $media_gallery = $product->getData('media_gallery');
            foreach ($media_gallery['images'] as $image) {
                $this->_removeFile(Mage::getSingleton('catalog/product_media_config')->
                                getMediaPath($image['file']));
            }
            $product->delete();
            return true;
        }


        $currentproducttype = $importData['type'];

        if ($importData['type'] == 'configurable') {
            $this->_processConfiguableProduct($product, $importData);
        }
        //THIS IS FOR DOWNLOADABLE PRODUCTS
        if ($importData['type'] == 'downloadable' && $importData['downloadable_options'] !=
                "") {
            $this->_processDownloadableProduct($product, $importData);
        }


        //THIS IS FOR BUNDLE PRODUCTS
        if ($importData['type'] == 'bundle') {
            $this->_processBundleProduct($product, $importData, $new);
        }
        if (isset($importData['related'])) {
            $linkIds = $this->skusToIds($importData['related'], $product);
            if (!empty($linkIds)) {
                $product->setRelatedLinkData($linkIds);
            }
        }

        if (isset($importData['upsell'])) {
            $linkIds = $this->skusToIds($importData['upsell'], $product);
            if (!empty($linkIds)) {
                $product->setUpSellLinkData($linkIds);
            }
        }

        if (isset($importData['crosssell'])) {
            $linkIds = $this->skusToIds($importData['crosssell'], $product);
            if (!empty($linkIds)) {
                $product->setCrossSellLinkData($linkIds);
            }
        }

        if (isset($importData['grouped'])) {
            $linkIds = $this->skusToIds($importData['grouped'], $product);
            if (!empty($linkIds)) {
                $product->setGroupedLinkData($linkIds);
            }
        }

        if (isset($importData['category_ids'])) {
            $product->setCategoryIds($importData['category_ids']);
        }

        if (isset($importData['tier_prices']) && !empty($importData['tier_prices'])) {
            $this->_editTierPrices($product, $importData['tier_prices']);
        }


        if (isset($importData['categories'])) {
            if (isset($importData['store'])) {
                $cat_store = $this->_stores[$importData['store']];
            } else {
                $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "store" for new products not defined', $field);
                $this->logErrors($message);
                Mage::throwException($message);
            }
            $categoryIds = $this->_addCategories($importData['categories'], $cat_store);
            if ($categoryIds) {
                $product->setCategoryIds($categoryIds);
            }
        }

        foreach ($this->_ignoreFields as $field) {
            if (isset($importData[$field])) {
                unset($importData[$field]);
            }
        }

        if ($store->getId() != 0) {
            $websiteIds = $product->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = array();
            }
            if (!in_array($store->getWebsiteId(), $websiteIds)) {
                $websiteIds[] = $store->getWebsiteId();
            }
            $product->setWebsiteIds($websiteIds);
        }

        if (isset($importData['websites'])) {
            $websiteIds = $product->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = array();
            }
            $websiteCodes = explode(',', $importData['websites']);
            foreach ($websiteCodes as $websiteCode) {
                try {
                    $website = Mage::app()->getWebsite(trim($websiteCode));
                    if (!in_array($website->getId(), $websiteIds)) {
                        $websiteIds[] = $website->getId();
                    }
                } catch (exception $e) {
                    
                }
            }
            $product->setWebsiteIds($websiteIds);
            unset($websiteIds);
        }

        $custom_options = array();
//print_r(date('i:s:u') . '______6' . "\n");
        foreach ($importData as $field => $value) {
            //SEEMS TO BE CONFLICTING ISSUES WITH THESE 2 CHOICES AND DOESNT SEEM TO REQUIRE THIS IN ALL THE TESTING SO LEAVING COMMENTED
            //if ( in_array( $field, $this -> _inventoryFields ) ) {
            //continue;
            //}
            /*
              if (in_array($field, $this->_inventorySimpleFields))
              {
              continue;
              }
             */
            if (in_array($field, $this->_imageFields)) {
                continue;
            }

            $attribute = $this->getAttribute($field);
            if (!$attribute) {
                /* CUSTOM OPTION CODE */
                if (strpos($field, ':') !== false && strlen($value)) {
                    $values = explode('|', $value);
                    if (count($values) > 0) {
                        $iscustomoptions = "true";

                        foreach ($values as $v) {
                            $parts = explode(':', $v);
                            $title = $parts[0];
                        }
                        //RANDOM ISSUE HERE SOMETIMES WITH TITLES OF LAST ITEM IN DROPDOWN SHOWING AS TITLE MIGHT NEED TO SEPERATE TITLE variables
                        @list($title, $type, $is_required, $sort_order) = explode(':', $field);
                        $title2 = ucfirst(str_replace('_', ' ', $title));
                        $custom_options[] = array('is_delete' => 0, 'title' => $title2, 'previous_group' =>
                            '', 'previous_type' => '', 'type' => $type, 'is_require' => $is_required,
                            'sort_order' => $sort_order, 'values' => array());
                        foreach ($values as $v) {
                            $parts = explode(':', $v);
                            $title = $parts[0];
                            if (count($parts) > 1) {
                                $price = $parts[1];
                            } else {
                                $price = 0;
                            }
                            if (count($parts) > 2) {
                                $price_type = $parts[2];
                            } else {
                                $price_type = 'fixed';
                            }
                            if (count($parts) > 3) {
                                $sku = $parts[3];
                            } else {
                                $sku = '';
                            }
                            if (count($parts) > 4) {
                                $sort_order = $parts[4];
                            } else {
                                $sort_order = 0;
                            }
                            if (count($parts) > 5) {
                                $file_extension = $parts[5];
                            } else {
                                $file_extension = '';
                            }
                            if (count($parts) > 6) {
                                $image_size_x = $parts[6];
                            } else {
                                $image_size_x = '';
                            }
                            if (count($parts) > 7) {
                                $image_size_y = $parts[7];
                            } else {
                                $image_size_y = '';
                            }
                            switch ($type) {
                                case 'file':
                                    $custom_options[count($custom_options) - 1]['price_type'] = $price_type;
                                    $custom_options[count($custom_options) - 1]['price'] = $price;
                                    $custom_options[count($custom_options) - 1]['sku'] = $sku;
                                    $custom_options[count($custom_options) - 1]['file_extension'] = $file_extension;
                                    $custom_options[count($custom_options) - 1]['image_size_x'] = $image_size_x;
                                    $custom_options[count($custom_options) - 1]['image_size_y'] = $image_size_y;
                                    break;

                                case 'field':
                                case 'area':
                                    $custom_options[count($custom_options) - 1]['max_characters'] = $sort_order;
                                /* NO BREAK */

                                case 'date':
                                case 'date_time':
                                case 'time':
                                    $custom_options[count($custom_options) - 1]['price_type'] = $price_type;
                                    $custom_options[count($custom_options) - 1]['price'] = $price;
                                    $custom_options[count($custom_options) - 1]['sku'] = $sku;
                                    break;

                                case 'drop_down':
                                case 'radio':
                                case 'checkbox':
                                case 'multiple':
                                default:
                                    $custom_options[count($custom_options) - 1]['values'][] = array('is_delete' => 0,
                                        'title' => $title, 'option_type_id' => -1, 'price_type' => $price_type, 'price' =>
                                        $price, 'sku' => $sku, 'sort_order' => $sort_order,);
                                    break;
                            }
                        }
                    }
                }
                /* END CUSTOM OPTION CODE */
                continue;
            }


            $isArray = false;
            $setValue = $value;

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = explode(self::MULTI_DELIMITER, $value);
                $value = array_unique($value);
                $isArray = true;
                $setValue = array();
            }

            if ($value && $attribute->getBackendType() == 'decimal') {
                $setValue = $this->getNumber($value);
            }

            /*             * CODE MODIFICATION STARTS HERE */

            if ($attribute->usesSource() && $field != 'udropship_vendor') {
                $setValue = $this->_getValueOptions($attribute, $field, $value, $isArray);
            }

            /*             * CODE MODIFICATION ENDS HERE */


            $product->setData($field, $setValue);
            //print_r(date('i:s:u') . '______' . $field . "\n");
            if ($field == 'udropship_vendor')
                $product->setData($field, $value);
        }

        if (!$product->getVisibility()) {
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::
                    VISIBILITY_NOT_VISIBLE);
        }
//print_r(date('i:s:u') . '______7' . "\n");
        $stockData = array();
        $inventoryFields = isset($this->_inventoryFieldsProductTypes[$product->
                                getTypeId()]) ? $this->_inventoryFieldsProductTypes[$product->getTypeId()] :
                array();

        foreach ($inventoryFields as $field) {
            if (isset($importData[$field])) {
                if (in_array($field, $this->_toNumber)) {
                    $stockData[$field] = $this->getNumber($importData[$field]);
                } else {
                    $stockData[$field] = $importData[$field];
                }
            }
        }
        $product->setStockData($stockData);
        //print_r($inventoryFields);exit();
        // hard code
        $product->setData('salable', 1);
        // -------
//print_r(date('i:s:u') . '______8' . "\n");
        //save Images
        $this->_saveImages($product, $importData, $keepImage, $new);
//print_r(date('i:s:u') . '______9' . "\n");        
        $product->setIsMassupdate(true);
        $product->setExcludeUrlRewrite(true);
        try {
            //print_r(date('i:s:u') . '______2' . "\n");
            $this->_beforeProductSave($product, $importData);

            $product->save();
            //print_r(date('i:s:u') . '______3' . "\n");
        } catch (exception $e) {
            $this->logErrors($e->getMessage());
        }

        if (isset($importData['product_tags'])) {

            $configProductTags = $this->userCSVDataAsArray($importData['product_tags']);

            #foreach ($commadelimiteddata as $dataseperated) {
            foreach ($configProductTags as $tagName) {
                if (empty($tagName))
                    continue;
                $commadelimiteddata = explode(':', $tagName);

                $tagName = ucfirst(str_replace('_', ' ', $commadelimiteddata[1]));
                $tagModel = Mage::getModel('tag/tag');
                $result = $tagModel->loadByName($tagName);
                #echo $result;
                #echo "PRODID: " . $product -> getIdBySku( $importData['sku'] ) . " Name: " . $tagName;
                $tagModel->setName($tagName)->setStoreId($importData['store'])->setStatus($tagModel->
                                getApprovedStatus())->save();

                $tagRelationModel = Mage::getModel('tag/tag_relation');
                /* $tagRelationModel->loadByTagCustomer($product -> getIdBySku( $importData['sku'] ), $tagModel->getId(), '13194', Mage::app()->getStore()->getId()); */

                $tagRelationModel->setTagId($tagModel->getId())->setCustomerId($commadelimiteddata[0])->
                        setProductId($product->getIdBySku($importData['sku']))->setStoreId($importData['store'])->
                        setCreatedAt(now())->setActive(1)->save();
                $tagModel->aggregate();
            }
        }

        /* Remove existing custom options attached to the product */
        foreach ($product->getOptions() as $o) {
            $o->getValueInstance()->deleteValue($o->getId());
            $o->deletePrices($o->getId());
            $o->deleteTitles($o->getId());
            $o->delete();
        }

        /* Add the custom options specified in the CSV import file */
        if (count($custom_options)) {
            foreach ($custom_options as $option) {
                try {
                    $opt = Mage::getModel('catalog/product_option');
                    $opt->setProduct($product);
                    $opt->addOption($option);
                    $opt->saveOptions();
                } catch (exception $e) {
                    
                }
            }
        }

        if ($iscustomoptions == "true" || $product->getData('has_options' == 1)) {
            ######### CUSTOM QUERY FIX FOR DISAPPEARING OPTIONS #################
            // fetch write database connection that is used in Mage_Core module

            if ($currentproducttype == "simple") {
                $prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix');
                $fixOptions = Mage::getSingleton('core/resource')->getConnection('core_write');
                // now $write is an instance of Zend_Db_Adapter_Abstract
                $fixOptions->query("UPDATE " . $prefix .
                        "catalog_product_entity SET has_options = 1 WHERE type_id = 'simple' AND entity_id IN (SELECT distinct(product_id) FROM " .
                        $prefix . "catalog_product_option)");
            } else
            if ($currentproducttype == "configurable") {
                $prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix');
                $fixOptions = Mage::getSingleton('core/resource')->getConnection('core_write');
                // now $write is an instance of Zend_Db_Adapter_Abstract
                $fixOptions->query("UPDATE " . $prefix .
                        "catalog_product_entity SET has_options = 1 WHERE type_id = 'configurable' AND entity_id IN (SELECT distinct(product_id) FROM " .
                        $prefix . "catalog_product_option)");
            }
        }


        /* DOWNLOADBLE PRODUCT FILE METHOD START */
        #print_r($filearrayforimport);
        /* if(isset($filearrayforimports)) {
          foreach($filearrayforimports as $filearrayforimport) {
          $document_directory = Mage :: getBaseDir( 'media' ) . DS . 'import' . DS;
          $files = $filearrayforimport['file'];
          #echo "FILE: " . $filearrayforimport['file'];
          #echo "ID: " . $product->getId();
          $resource = Mage::getSingleton('core/resource');
          $prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix');
          $write = $resource->getConnection('core_write');
          $read = $resource->getConnection('core_read');
          $select_qry =$read->query("SHOW TABLE STATUS LIKE '".$prefix."downloadable_link' ");
          $row = $select_qry->fetch();
          $next_id = $row['Auto_increment'];

          $linkModel = Mage::getModel('downloadable/link')->load($next_id-1);

          $link_file = 	$document_directory . $files;

          $file = realpath($link_file);
          Mage::log(print_r($product->getDownloadableData(), true), null, 'import.log');
          if (!$file || !file_exists($file)) {
          Mage::throwException(Mage::helper('catalog')->__('Link  file '.$file.' not exists'));
          }
          $pathinfo = pathinfo($file);


          $linkfile       = Varien_File_Uploader::getCorrectFileName($pathinfo['basename']);
          $dispretionPath = Varien_File_Uploader::getDispretionPath($linkfile);
          $linkfile       = $dispretionPath . DS . $linkfile;

          $linkfile = $dispretionPath . DS
          . Varien_File_Uploader::getNewFileName(Mage_Downloadable_Model_Link::getBasePath().DS.$linkfile);

          $ioAdapter = new Varien_Io_File();
          $ioAdapter->setAllowCreateFolders(true);
          $distanationDirectory = dirname(Mage_Downloadable_Model_Link::getBasePath().DS.$linkfile);

          try {
          $ioAdapter->open(array(
          'path'=>$distanationDirectory
          ));

          $ioAdapter->cp($file, Mage_Downloadable_Model_Link::getBasePath().DS.$linkfile);
          $ioAdapter->chmod(Mage_Downloadable_Model_Link::getBasePath().DS.$linkfile, 0777);

          }
          catch (Exception $e) {
          Mage::throwException(Mage::helper('catalog')->__('Failed to move file: %s', $e->getMessage()));
          }

          $linkfile = str_replace(DS, '/', $linkfile);


          $linkModel->setLinkFile($linkfile);
          $linkModel->save();

          $intesdf = $next_id-1;
          $write->query("UPDATE `".$prefix."downloadable_link_title` SET title = '".$filearrayforimport['name']."' WHERE link_id = '".$intesdf."'");
          $write->query("UPDATE `".$prefix."downloadable_link_price` SET price = '".$filearrayforimport['price']."' WHERE link_id = '".$intesdf."'");
          #$product->setLinksPurchasedSeparately(false);
          #$product->setLinksPurchasedSeparately(0);
          }
          } */
        /* END DOWNLOADBLE METHOD */

        /* START OF SUPER ATTRIBUTE PRICING */


        if ($finalsuperattributetype == 'configurable') {
            $resource = Mage::getSingleton('core/resource');
            $adapter = $resource->getConnection('core_write');
            $read = $resource->getConnection('core_read');
            $superProduct = Mage::getModel('catalog/product')->load($product->getId());
            $associatedProducts = $superProduct->getTypeInstance(true)->getUsedProductCollection($product);
            $configAttrs = $superProduct->getTypeInstance()->getConfigurableAttributes();
            $result = array();
            $pricingFields = explode(',', $this->__pricingFields);
            foreach ($associatedProducts as $_product) {
                $_product->load($_product->getId());
                foreach ($configAttrs as $_attr) {
                    if (!empty($pricingFields) && in_array($_attr->getProductAttribute()->getAttributeCode(), $pricingFields)) {
                        $value = $_product->getAttributeText($_attr->getProductAttribute()->getAttributeCode());
                        $value_index = $_product->getData($_attr->getProductAttribute()->getAttributeCode());
                        $result[$value_index] = array(
                            'product_super_attribute_id' => $_attr->getProductSuperAttributeId(),
                            'label' => $value,
                            'value_index' => $value_index,
                            'attribute_id' => $_attr->getProductAttribute()->getId(),
                            'pricing_value' => ($_product->getPrice() - $superProduct->getPrice())
                        );
                    }
                }
            }
            if (count($result) > 0) {
                $insertPrice = 'INSERT INTO ' . $resource->getTableName('catalog_product_super_attribute_pricing') . ' (product_super_attribute_id, value_index, is_percent, pricing_value) VALUES ';
                $firstFlag = true;
                foreach ($result as $_row) {
                    $checkExist = $read->fetchOne('SELECT COUNT(*) FROM ' . $resource->getTableName('catalog_product_super_attribute_pricing') . ' WHERE `product_super_attribute_id` = "' . $_row['product_super_attribute_id'] . '" AND `value_index` = "' . $_row['value_index'] . '"');
                    if ($checkExist > 0) {
                        $adapter->query('UPDATE ' . $resource->getTableName('catalog_product_super_attribute_pricing') . '
										SET `is_percent` = "0", `pricing_value` = "' . $_row['pricing_value'] . '"
										WHERE `product_super_attribute_id` = "' . $_row['product_super_attribute_id'] . '" AND `value_index` = "' . $_row['value_index'] . '"
            			');
                        continue;
                        $allwayContinue = false;
                    }
                    if (!$firstFlag) {
                        $insertPrice .= ',';
                    }
                    $insertPrice .= '("' . $_row['product_super_attribute_id'] . '", "' . $_row['value_index'] . '", "0", "' . $_row['pricing_value'] . '")';
                    $firstFlag = false;
                }
                $insertPrice .= ';';
                //var_dump("sql: ".$insertPrice);
                try {
                    $adapter->query($insertPrice);    
                } catch (Exception $exc) {
                    //echo $exc->getTraceAsString();
                }
            }
            if ($finalsuperattributepricing != "") {

                //$adapter = Mage::getSingleton('core/resource')->getConnection('core_write');
                $superProduct = Mage::getModel('catalog/product')->load($product->getId());
                $superArray = $superProduct->getTypeInstance(true)->
                        getConfigurableAttributesAsArray($product);

                #print_r($superArray);

                $SuperAttributePricingData = array();
                $FinalSuperAttributeData = array();
                $SuperAttributePricingData = explode('|', $finalsuperattributepricing);

                $prefix = Mage::getConfig()->getNode('global/resources/db/table_prefix');
                foreach ($superArray as $key => $val) {
                    #$x = 0 ;
                    foreach ($val['values'] as $keyValues => $valValues) {

                        foreach ($SuperAttributePricingData as $singleattributeData) {
                            $FinalSuperAttributeData = explode(':', $singleattributeData);

                            if ($FinalSuperAttributeData[0] == $superArray[$key]['values'][$keyValues]['label']) {
                            	$insertPrice = 'INSERT into ' . $prefix .
                                        'catalog_product_super_attribute_pricing (product_super_attribute_id, value_index, is_percent, pricing_value) VALUES
												 ("' . $superArray[$key]['values'][$keyValues]['product_super_attribute_id'] .
                                        '", "' . $superArray[$key]['values'][$keyValues]['value_index'] . '", "' . $FinalSuperAttributeData[2] .
                                        '", "' . $FinalSuperAttributeData[1] . '");';
                                
                            	$checkExist = $read->fetchOne('SELECT COUNT(*) FROM ' . $prefix .
                                        'catalog_product_super_attribute_pricing WHERE `product_super_attribute_id` = "' . $superArray[$key]['values'][$keyValues]['product_super_attribute_id'] . '" AND `value_index` = "' . $superArray[$key]['values'][$keyValues]['value_index'] . '"');
                                var_dump("exists: ".$checkExist);
                            	if ($checkExist > 0) {
			                        $adapter->query('UPDATE ' . $resource->getTableName('catalog_product_super_attribute_pricing') . '
													SET `is_percent` = "'.$FinalSuperAttributeData[2].'", `pricing_value` = "' . $FinalSuperAttributeData[1] . '"
													WHERE `product_super_attribute_id` = "' . $superArray[$key]['values'][$keyValues]['product_super_attribute_id'] . '" AND `value_index` = "' . $superArray[$key]['values'][$keyValues]['value_index'] . '"
			            			');
			                        //continue;
			                        //$allwayContinue = false;
			                    }
                                else {
	                                #echo "SQL2: " . $insertPrice;
	                                $adapter->query($insertPrice);
                                }
                            }
                        }
                    }
                }
            }
        }
        /* END OF SUPER ATTRIBUTE PRICING */

        /* $this->addCountImportRow();
          $this->saveAfterImportRow($product,$importData); */

        //$this->__newProductData = $product->toArray();                
        //print_r(date('i:s:u') . '______4' . "\n");
        return true;
    }

    /**
     * Edit tier prices
     *
     * Uses a pipe-delimited string of qty:price to set tiers for the product row and appends.
     * Removes if REMOVE is present.
     *
     * @todo Prevent duplicate tiers (by qty) being set
     * @internal Magento will save duplicate tiers; no enforcing unique tiers by qty, so we have to do this manually
     * @param Mage_Catalog_Model_Product $product Current product row
     * @param string $tier_prices_field Pipe-separated in the form of qty:price (e.g. 0=250=12.75|0=500=12.00)
     */
    private function _editTierPrices(&$product, $tier_prices_field = false) {
        if (($tier_prices_field) && !empty($tier_prices_field)) {

            if (trim($tier_prices_field) == 'REMOVE') {

                $product->setTierPrice(array());
            } else {


                //get current product tier prices
                $existing_tps = $product->getTierPrice();

                $etp_lookup = array();
                //make a lookup array to prevent dup tiers by qty
                foreach ($existing_tps as $key => $etp) {
                    $etp_lookup[intval($etp['price_qty'])] = $key;
                }

                //parse incoming tier prices string
                $incoming_tierps = explode('|', $tier_prices_field);
                $tps_toAdd = array();
                $tierpricecount = 0;
                foreach ($incoming_tierps as $tier_str) {
                    //echo "t: " . $tier_str;
                    if (empty($tier_str))
                        continue;

                    $tmp = array();
                    $tmp = explode('=', $tier_str);

                    if ($tmp[1] == 0 && $tmp[2] == 0)
                        continue;
                    //echo ('adding tier');
                    //print_r($tmp);
                    $tps_toAdd[$tierpricecount] = array('website_id' => 0,
                        // !!!! this is hard-coded for now
                        'cust_group' => $tmp[0], // !!! so is this
                        'price_qty' => $tmp[1], 'price' => $tmp[2], 'delete' => '');

                    //drop any existing tier values by qty
                    if (isset($etp_lookup[intval($tmp[1])])) {
                        unset($existing_tps[$etp_lookup[intval($tmp[1])]]);
                    }
                    $tierpricecount++;
                }

                //combine array
                $tps_toAdd = array_merge($existing_tps, $tps_toAdd);

                //print_r($tps_toAdd);
                //save it
                $product->setTierPrice($tps_toAdd);
            }
        }
    }

    protected function userCSVDataAsArray($data) {
        return explode(',', str_replace(" ", "", $data));
    }

    protected function skusToIds($userData, $product) {
        $productIds = array();
        foreach (explode(',', $userData) as $oneSku) {
            $oneSku = trim($oneSku);
            if (($a_sku = (int)$product->getIdBySku($oneSku)) > 0) {
                parse_str("position=", $productIds[$a_sku]);
            }
        }
        return $productIds;
    }

    protected $_categoryCache = array();

    protected function _addCategories($categories, $store) {
    	$rootId = $store->getRootCategoryId();
        if (!$rootId) {
            return array();
        }
        $rootPath = '1/' . $rootId;
        if (empty($this->_categoryCache[$store->getId()])) {

            $collection = Mage::getModel('catalog/category')->getCollection()->setStore($store)->
                    addAttributeToSelect('name');
            $collection->getSelect()->where("path like '" . $rootPath . "/%'");
            foreach ($collection as $cat) {
                $pathArr = explode('/', $cat->getPath());
                $namePath = '';
                for ($i = 2, $l = sizeof($pathArr); $i < $l; $i++) {
                    $name = $collection->getItemById($pathArr[$i])->getName();
                    $namePath .= (empty($namePath) ? '' : '/') . trim($name);
                }
                $cat->setNamePath($namePath);
            }

            $cache = array();
            foreach ($collection as $cat) {
                $cache[strtolower($cat->getNamePath())] = $cat;
                $cat->unsNamePath();
            }
            $this->_categoryCache[$store->getId()] = $cache;
        }
        $cache = &$this->_categoryCache[$store->getId()];

        $catIds = array();
        //Delimiter is ' , ' so people can use ', ' in multiple categorynames
        foreach (explode(',', $categories) as $categoryPathStr) {
            $categoryPathStr = preg_replace('#\s*/\s*#', '/', trim($categoryPathStr));
            if (!empty($cache[$categoryPathStr])) {
                $catIds[] = $cache[$categoryPathStr]->getId();
                continue;
            }
            $path = $rootPath;
            $namePath = '';
            foreach (explode('/', $categoryPathStr) as $catName) {
                $namePath .= (empty($namePath) ? '' : '/') . strtolower($catName);
                if (empty($cache[$namePath])) {
                    $cat = Mage::getModel('catalog/category')->setStoreId($store->getId())->setPath($path)->
                                    setName($catName)->setIsActive(1)->setIsAnchor(1)->save();
                    $cache[$namePath] = $cat;
                }
                $catId = $cache[$namePath]->getId();
                $path .= '/' . $catId;
            }
            if ($catId) {
                $catIds[] = $catId;
            }
        }
        return join(',', $catIds);
    }

    protected function _removeFile($file) {
        if (is_file($file)) {
            if (@unlink($file)) {
                return true;
            }
        }
        return false;
    }

    public function updateSourceAndReturnId($attribute_code, $newOption) {
        /* $attribute_model        = Mage::getModel('eav/entity_attribute');
          $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;

          $attribute_code         = $attribute_model->getIdByCode('catalog_product', $attribute_code);
          $attribute              = $attribute_model->load($attribute_code);
          $attribute_table        = $attribute_options_model->setAttribute($attribute);

          $options = $attribute_options_model->getAllOptions(false); */
        $newOption = trim($newOption);
        if (empty($newOption))
            return '';
        /* foreach($options as $option)
          {
          if (strcasecmp($option['label'],$newOption) == 0)
          {
          return $option['value'];
          }
          } */
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute_code);
        foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
            if (strcasecmp($option['label'], $newOption) == 0) {
                return $option['value'];
            }
        }
        try {
            $value['option'] = array($newOption, $newOption);
            $result = array('value' => $value);
            $attribute->setData('option', $result);
            $attribute->save();
        } catch (Exception $e) {
            
        }
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute_code);
        foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
            if (strcasecmp($option['label'], $newOption) == 0) {
                return $option['value'];
            }
        }

        /* $options = $attribute_options_model->getAllOptions(true);
          foreach($options as $option)
          {
          if (strcasecmp($option['label'],$newOption) == 0)
          {
          return $option['value'];
          }
          } */
        return "";
    }

    /**
     * Retrieve product model cache
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProductModel() {
        /* if($type == 'simple'){
          if (is_null($this->_productModel)) {
          $productModel = Mage::getModel('catalog/productimport');
          $this->_productModel = Mage::objects()->save($productModel);
          }
          return Mage::objects()->load($this->_productModel);
          } else {
          if (is_null($this->_productConfModel)) {
          $productModel = Mage::getModel('catalog/productimport');
          $this->_productConfModel = Mage::objects()->save($productModel);
          }
          return Mage::objects()->load($this->_productConfModel);
          } */
        if (is_null($this->_productModel)) {
            $productModel = Mage::getModel('catalog/import_product');
            $this->_productModel = Mage::objects()->save($productModel);
        }
        return Mage::objects()->load($this->_productModel);
    }

    public function parse() {
        $batchModel = Mage::getSingleton('dataflow/batch');
        /* @var $batchModel Mage_Dataflow_Model_Batch */

        $batchImportModel = $batchModel->getBatchImportModel();
        $importIds = $batchImportModel->getIdCollection();

        foreach ($importIds as $importId) {
            //print '<pre>'.memory_get_usage().'</pre>';
            $batchImportModel->load($importId);
            $importData = $batchImportModel->getBatchData();
            try {
                $this->saveRow($importData);
            } catch (Exception $e) {
                $this->logErrors($e->getMessages());
            }
        }
    }

    /**
     * inherited functions
     */
    protected function convertData($fromdata, &$todata, $map) {
        foreach ($map as $to => $from) {
            if (is_array($from)) {
                foreach ($from as $f) {
                    extract($f);
                    if (isset($fromdata[$field]))
                        if (!empty($fromdata[$field]))
                            $todata[$to] .= $prefix . $fromdata[$field] . $suffix;
                }
            } else if (isset($fromdata[$from]))
                $todata[$to] = $fromdata[$from];
        }
        return $todata;
    }

    protected function logErrors($message, $level = null) {
        $filename = ($this->__isUpdate) ? 'updateproducts_error.log' : 'importproducts_error.log';

        if (!is_dir(Mage::getBaseDir('var') . DS . 'log' . DS . 'import' . DS . $this->__vendorName)) {
            mkdir(Mage::getBaseDir('var') . DS . 'log' . DS . 'import' . DS . $this->__vendorName, 0777, true);
        }

        Mage::log('record ' . $this->getCurrentPosition() . ': ' . $message, $level, 'import' . DS . $this->__vendorName . DS . $filename);
    }

    protected function trimData($data) {
        $ret_data = array();
        foreach ($data as $k => $v) {
            $ret_data[trim($k)] = trim($v);
        }
        return $ret_data;
    }

    protected function getFtp($args) {
        if (!$this->__ftp) {
            $this->__ftp = Mage::getModel('varien/io_ftp');
            try {
                $this->__ftp->open($args);
            } catch (Varien_Io_Exception $e) {
                $this->logErrors($e->getMessage());
            }
        }

        return $this->__ftp;
    }

    protected function getImageHttp($url, $sku, $override = true, $ext = 'jpeg') {
        if (empty($url))
            return '';
        $imageName = $sku . '.' . $ext;
        $img = Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $imageName;
        if (!file_exists(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName))
            mkdir(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName, 0777, true);
        else {
            if (!$override)
                if (file_exists($img))
                    return $imageName;
        }
        $url = str_replace(' ', '%20', $url);
        if (file_put_contents($img, file_get_contents($url)) > 0) {
            return $imageName;
        } else {
            @unlink($img);
            $this->logErrors('[sku:' . $sku . ']' . ' have not ' . $url . ' image');
        }

        return '';
    }

    protected function getImageFtp($imageName, $sku, $override = true, $dir = '') {
        $args = $this->getBatchParams();

        $ftp_args = array(
            'host' => $this->getBatchParams('image_ftp_host'),
            'user' => $this->getBatchParams('image_ftp_user'),
            'password' => $this->getBatchParams('image_ftp_password'),
            'path' => $this->getBatchParams('image_ftp_path')
        );

        if ($this->getBatchParams('image_ftp_transfer_mode') == 'passive')
            $ftp_args['passive'] = true;

        $ftp = $this->getFtp($ftp_args);

        $dir_name = DS;
        if ($dir) {
            $dir_name = DS . str_replace(DS, '_', $dir);
        }

        if (!file_exists(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName))
            mkdir(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName, 0777, true);
        else {
            if (!$override)
                if (file_exists(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . $dir_name . $imageName))
                    return true;
        }

        if ($dir)
            $ftp->cd($dir);
        if (!$ftp->read($imageName, Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . $dir_name . $imageName)) {
            $this->logErrors('[sku:' . $imageName . '] Downloading image is fail');
            return false;
        }

        return true;
    }

    protected function getImageListFromFtp($args, $deepPath = '') {
        if (empty($this->__listFtpImages)) {

            if ($this->getBatchParams('image_ftp_transfer_mode') == 'passive')
                $args['passive'] = true;

            $this->__listFtpImages = array();
            $ftp = $this->getFtp($args);
            if (is_array($deepPath)) {
                foreach ($deepPath as $path) {
                    $folder = str_replace('//', '/', $this->getBatchParams('image_ftp_path') . $path);
                    $folder = DS . trim($folder, DS);
                    $ftp->cd($folder);
                    $this->__listFtpImages[$folder] = $this->array_sort($ftp->ls(), 'text', SORT_ASC);
                }
            } else {
                $this->__listFtpImages = $this->array_sort($ftp->ls(), 'text', SORT_ASC);
            }
        }

        return $this->__listFtpImages;
    }

    protected function isFtpFile($filename) {
        if (preg_match('/\.(\w)+\z/s', $filename)) {
            return true;
        }
        return false;
    }

    protected function doDataFilter($data) {
        if ($this->__isUpdate) {
            $this->_doDataFilterUpdate($data);
        } else {
            $this->_doDataFilterInsert($data);
        }

        return $this->__dataBeSaved;
    }

    protected function _doDataFilterInsert($data) {
        $this->__dataBeSaved += $this->__defaultMasterAttributes;

        /* if($udropship = $this->getUdropship()){
          $this->__dataBeSaved['udropship_vendor'] = $udropship;
          } */
    }

    protected function _doDataFilterUpdate($data) {
        $this->__dataBeSaved += $this->__defaultUpdateAttributes;
    }

    protected function getUdropship() {
        if (!isset($this->__udropship)) {
            $batchData = $this->getBatchParams();
            if (isset($batchData['vendor_email'])) {
                $vendorEmail = $batchData['vendor_email'];
                $vendor = Mage::getModel('udropship/vendor');
                $vendor->getByEmail($vendorEmail);
                $this->__udropship = $vendor->getId();
            }
            else
                $this->__udropship = false;
        }
        return $this->__udropship;
    }

    protected function checkBeforeSave(&$data) {
        $this->setCurrentPosition($data['__current_position']);
        $data = $this->trimData($data);
        if (!isset($data[$this->__skuFieldName])) {
            $this->logErrors('Sku is empty, ignore this item');
            return false;
        }
        if (empty($data[$this->__skuFieldName])) {
            $this->logErrors('Sku is empty, ignore this item');
            return false;
        }
        return true;
    }

    public function array_sort($array, $on, $order = SORT_ASC) {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    public function finish() {
        //$this->cleanup();
        //$this->updatePosition();
    }

    /*
     * Save after import
     */

    protected function convertCSVtoAssocMArray($file, $delimiter = ',') {
        $result = Array();
        $size = filesize($file) + 1;
        $file = fopen($file, 'r');
        $keys = fgetcsv($file, $size, $delimiter);
        while ($row = fgetcsv($file, $size, $delimiter)) {
            for ($i = 0; $i < count($row); $i++) {
                if (array_key_exists($i, $keys)) {
                    $row[$keys[$i]] = $row[$i];
                }
            }
            $result[$row[0]] = $row;
        }
        fclose($file);
        return $result;
    }

    function getFilePathForAfterImport() {
        return Mage::getBaseDir('var') . '/afterimport/' . $this->__vendorName;
    }

    function getFileHandlerForAfterImport($product, $row) {
        $path = $this->getFilePathForAfterImport();
        if (!$this->__fileAfterImport) {
            if (!file_exists($path))
                mkdir($path, 0775, true);

            if ($this->getCountImportRow() > 1) {
                $this->__fileAfterImport = fopen($path . '/afterimport.csv', 'a');
            } else {
                $this->__fileAfterImport = fopen($path . '/afterimport.csv', 'w');
                fputcsv($this->__fileAfterImport, array_keys($this->getAfterImportRow($product, $row)), ',');
            }
        }

        return $this->__fileAfterImport;
    }

    function updatePosition() {
        $path = $this->getFilePathForAfterImport();
        $importedProducts = $this->convertCSVtoAssocMArray($path . '/afterimport.csv');

        if (count($importedProducts)) {
            $product_ids = array_keys($importedProducts);
            $product_ids = join(',', $product_ids);
            $this->logErrors($product_ids);
            /* $mysqli = Mage::getSingleton('core/resource')->getConnection('core_write');
              $sql = "UPDATE
              catalog_category_product_index
              SET
              `position`=position+49
              WHERE
              catalog_category_product_index.`position` MOD 1000=1 AND catalog_category_product_index.`product_id` in ($product_ids)";

              $mysqli->query($sql);
              $sql = "UPDATE
              catalog_category_product
              SET
              `position`=position+49
              WHERE
              catalog_category_product.`position` MOD 1000=1 AND catalog_category_product.`product_id` in ($product_ids)";

              $mysqli->query($sql); */
        }
    }

    protected function saveAfterImportRow($product, $row) {
        $dataOnSave = $this->getAfterImportRow($product, $row);
        if (empty($dataOnSave)) {
            if ($this->getCountImportRow() % $this->getBatchParams('number_of_records') == 0)
                fclose($f);
            return;
        }
        $f = $this->getFileHandlerForAfterImport($product, $row);
        fputcsv($f, $dataOnSave);
        if ($this->getCountImportRow() % $this->getBatchParams('number_of_records') == 0)
            fclose($f);
    }

    protected function getAfterImportRow($product, $row) {
        return array(
            '#' => $this->getCountImportRow(),
            'id' => $product->getId(),
            'sku' => $product->getSku()
        );
    }

    //Start row count functions
    protected function addCountImportRow() {
        $count = Mage::getSingleton('core/session')->getImportRowCount();
        if (!$count)
            $count = 0;
        $count++;
        Mage::getSingleton('core/session')->setImportRowCount($count);
        return $count;
    }

    protected function getCountImportRow() {
        return Mage::getSingleton('core/session')->getImportRowCount();
    }

    protected function cleanup() {
        Mage::getSingleton('core/session')->unsImportRowCount(null);
    }

    //End row count functions
    //Start group product functions
    //End group product functions

    public function setCurrentPosition($pos) {
        $this->__current_position = $pos;
    }

    public function getCurrentPosition() {
        return $this->__current_position;
    }

    protected function _saveImages($product, $importData, $keepImage, $new) {
        $imageData = array();
        foreach ($this->_imageFields as $field) {
            if (!empty($importData[$field]) && $importData[$field] != 'no_selection') {
                if (!isset($imageData[$importData[$field]])) {
                    $imageData[$importData[$field]] = array();
                }
                $imageData[$importData[$field]][] = $field;
            }
        }
        if ($new) { //starts CHECK FOR IF NEW PRODUCT
            foreach ($imageData as $file => $fields) {
                try {
                    if ($this->getBatchParams('exclude_images') == "true") {
                        $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $file, $fields, false);
                    } else {
                        $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $file, $fields, false, false);
                    }
                } catch (exception $e) {
                    
                }
            }

            if (!empty($importData['gallery'])) {
                $galleryData = explode(',', $importData["gallery"]);
                foreach ($galleryData as $gallery_img) {
                    try {
                        if ($this->getBatchParams('exclude_images') == "true") {
                            $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $gallery_img, null, false);
                        } else {
                            $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $gallery_img, null, false, false);
                        }
                    } catch (exception $e) {
                        
                    }
                }
            }
        } elseif (!$keepImage) { //not new, so delete old image info from db and remove the image from the media dir
            $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
            if (isset($attributes['media_gallery'])) {
                $gallery = $attributes['media_gallery'];
                //Get the images
                $galleryData = $product->getMediaGallery();
                foreach ($galleryData['images'] as $image) {
                    //If image exists
                    if ($gallery->getBackend()->getImage($product, $image['file'])) {
                        $gallery->getBackend()->removeImage($product, $image['file']);
                        $this->_removeFile(Mage::getSingleton('catalog/product_media_config')->
                                        getMediaPath($image['file']));
                    }
                }
            }

            // now import/create the image regardless if the product is new or not.
            $imageData = array();
            foreach ($this->_imageFields as $field) {
                if (!empty($importData[$field]) && $importData[$field] != 'no_selection') {
                    if (!isset($imageData[$importData[$field]])) {
                        $imageData[$importData[$field]] = array();
                    }
                    $imageData[$importData[$field]][] = $field;
                }
            }

            foreach ($imageData as $file => $fields) {
                try {
                    #$product -> addImageToMediaGallery( Mage :: getBaseDir( 'media' ) . DS . 'import/' . $file, $fields, false, true );

                    if ($this->getBatchParams('exclude_images') == "true") {
                        $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $file, $fields, false);
                    } else {
                        $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $file, $fields, false, false);
                    }
                } catch (exception $e) {
                    
                }
            }

            if (!empty($importData['gallery'])) {
                $galleryData = explode(',', $importData["gallery"]);
                foreach ($galleryData as $gallery_img) {
                    try {
                        if ($this->getBatchParams('exclude_images') == "true") {
                            $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $gallery_img, null, false, true);
                        } else {
                            $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS . $gallery_img, null, false, false);
                        }
                    } catch (exception $e) {
                        
                    }
                }
            }
        }
    }

    protected function _processConfiguableProduct($product, &$importData) {
        $product->setCanSaveConfigurableAttributes(true);
        $configAttributeCodes = $this->userCSVDataAsArray($importData['config_attributes']);
        $usingAttributeIds = array();

        /*         * *
         * Check the product's super attributes (see catalog_product_super_attribute table), and make a determination that way.
         * */


        /* $cspa = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
          $attr_codes = array();
          if (isset($cspa) && !empty($cspa)) { //found attributes
          foreach ($cspa as $cs_attr) {
          //$attr_codes[$cs_attr['attribute_id']] = $cs_attr['attribute_code'];
          $attr_codes[] = $cs_attr['attribute_id'];
          }
          } */
        $oldConfigurableAttributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);

        foreach ($configAttributeCodes as $attributeCode) {
            //$attribute = $product->getResource()->getAttribute($attributeCode);

            $attribute_model = Mage::getModel('eav/entity_attribute');
            $attribute_code = $attribute_model->getIdByCode('catalog_product', $attributeCode);
            $attribute = $attribute_model->load($attribute_code);


            if ($product->getTypeInstance(true)->canUseAttribute($attribute)) {
                //if (!in_array($attributeCode,$attr_codes)) { // fix for duplicating attributes error
                //if ($new) { // fix for duplicating attributes error // <---------- this must be true to fill $usingAttributes
                $usingAttributeIds[$attribute->getAttributeId()] = $attribute->getAttributeId();
                //}
            }
        }

        $tempConfigurableAttributes = $usingAttributeIds;
        $newConfigurableAttributeIds = array();
        $delConfigurableAttributes = array();
        foreach ($oldConfigurableAttributes as $configAttr) {
            if (!array_key_exists($configAttr['attribute_id'], $usingAttributeIds)) {
                $delConfigurableAttributes[$configAttr['attribute_id']] = $configAttr['attribute_id'];
            } else {
                unset($tempConfigurableAttributes[$configAttr['attribute_id']]);
            }
        }
        $newConfigurableAttributeIds = $tempConfigurableAttributes;

        $__ConfigurableAttributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
        foreach ($__ConfigurableAttributes as $__ConfigurableAttribute) {
            if (array_key_exists($__ConfigurableAttribute->getAttributeId(), $delConfigurableAttributes))
                $__ConfigurableAttribute->delete();
        }

        if (!empty($newConfigurableAttributeIds)) {
            $product->getTypeInstance(true)->setUsedProductAttributeIds($newConfigurableAttributeIds, $product);

            $updateconfigurablearray = array();
            $insidearraycount = 0;

            $newConfigurableAttributes = $product->getTypeInstance(true)->
                    getConfigurableAttributesAsArray($product);

            /* $updateconfigurablearray = $product->getTypeInstance(true)->
              getConfigurableAttributesAsArray($product);
              foreach ($updateconfigurablearray as $eacharrayvalue) {
              $finalarraytoimport[$insidearraycount]['label'] = $eacharrayvalue['frontend_label'];
              #$finalarraytoimport[$insidearraycount]['values'] = array( );
              #$attribute = Mage::getModel('catalog/product_type_configurable_attribute')->setProductAttribute($eacharrayvalue['attribute_id']);
              #$attribute->setStoreLabel($eacharrayvalue['frontend_label']);
              #print_r($attribute->getStoreLabel());
              $insidearraycount += 1;
              } */
            #$attribute->save();
            #print_r($finalarraytoimport);
            #$product->setConfigurableAttributesData($product->getTypeInstance()->getConfigurableAttributesAsArray());

            $finalarraytoimport = array();
            foreach ($oldConfigurableAttributes as $configAttr) {
                $finalarraytoimport[$configAttr['attribute_id']] = $configAttr;
            }
            foreach ($newConfigurableAttributes as $configAttr) {
                $finalarraytoimport[$configAttr['attribute_id']] = $configAttr;
            }

            $finalConfigurableAttributes = array();
            $posAttr = 0;
            foreach ($usingAttributeIds as $usingAttributeId) {
                $finalarraytoimport[$usingAttributeId]['position'] = $posAttr;
                $finalarraytoimport[$usingAttributeId]['label'] = $finalarraytoimport[$usingAttributeId]['frontend_label'];
                $finalConfigurableAttributes[] = $finalarraytoimport[$usingAttributeId];
                $posAttr++;
            }

            $product->setConfigurableAttributesData($finalConfigurableAttributes);
            $product->setCanSaveConfigurableAttributes(true);
            $product->setCanSaveCustomOptions(true);
        }
        //print_r($product->getConfigurableAttributesData());
        if (isset($importData['associated'])) {
            $product->setConfigurableProductsData($this->skusToIds($importData['associated'], $product));
            $importData['has_options'] = '1';
        }
        
    }

    protected function _processDownloadableProduct($product, &$importData) {
        // comment if --------------------------
        //if ($new) {
        $filearrayforimports = array();

        $downloadableitems = array();
        $downloadableitemsoptionscount = 0;
        //THIS IS FOR DOWNLOADABLE OPTIONS
        $commadelimiteddata = explode('|', $importData['downloadable_options']);
        foreach ($commadelimiteddata as $data) {
            $configBundleOptionsCodes = $this->userCSVDataAsArray($data);

            $downloadableitems['link'][$downloadableitemsoptionscount]['is_delete'] = 0;
            $downloadableitems['link'][$downloadableitemsoptionscount]['link_id'] = 0;
            $downloadableitems['link'][$downloadableitemsoptionscount]['title'] = $configBundleOptionsCodes[0];
            $downloadableitems['link'][$downloadableitemsoptionscount]['price'] = $configBundleOptionsCodes[1];
            $downloadableitems['link'][$downloadableitemsoptionscount]['number_of_downloads'] =
                    $configBundleOptionsCodes[2];
            $downloadableitems['link'][$downloadableitemsoptionscount]['is_shareable'] = 2;
            if (isset($configBundleOptionsCodes[5])) {
                #$downloadableitems['link'][$downloadableitemsoptionscount]['sample'] = '';
                $downloadableitems['link'][$downloadableitemsoptionscount]['sample'] = array('file' =>
                    '[]', 'type' => 'url', 'url' => '' . $configBundleOptionsCodes[5] . '');
            } else {
                $downloadableitems['link'][$downloadableitemsoptionscount]['sample'] = '';
            }
            $downloadableitems['link'][$downloadableitemsoptionscount]['file'] = '';
            $downloadableitems['link'][$downloadableitemsoptionscount]['type'] = $configBundleOptionsCodes[3];
            #$downloadableitems['link'][$downloadableitemsoptionscount]['link_url'] = $configBundleOptionsCodes[4];
            if ($configBundleOptionsCodes[3] == "file") {

                #$filearrayforimport = array('file'  => 'media/import/mypdf.pdf' , 'name'  => 'asdad.txt', 'size'  => '316', 'status'  => 'old');
                #$document_directory =  Mage :: getBaseDir( 'media' ) . DS . 'import' . DS;
                #echo "DIRECTORY: " . $document_directory;
                #$filearrayforimport = '[{"file": "/home/discou33/public_html/media/import/mypdf.pdf", "name": "mypdf.pdf", "status": "new"}]';
                #$filearrayforimport = '[{"file": "mypdf.pdf", "name": "quickstart.pdf", "size": 324075, "status": "new"}]';
                #$product->setLinksPurchasedSeparately(0);
                #$product->setLinksPurchasedSeparately(false);
                #$files = Zend_Json::decode($filearrayforimport);
                #$files = "mypdf.pdf";
                //--------------- upload file ------------------                     
                $document_directory = Mage::getBaseDir('media') . DS . 'import' . DS . $this->__vendorName . DS;
                $files = '' . $configBundleOptionsCodes[4] . '';
                $link_file = $document_directory . $files;
                $file = realpath($link_file);
                if (!$file || !file_exists($file)) {
                    Mage::throwException(Mage::helper('catalog')->__($rowInfo . 'Link  file ' . $file .
                                    ' not exists'));
                }
                $pathinfo = pathinfo($file);


                $linkfile = Varien_File_Uploader::getCorrectFileName($pathinfo['basename']);
                $dispretionPath = Varien_File_Uploader::getDispretionPath($linkfile);
                $linkfile = $dispretionPath . DS . $linkfile;

                $linkfile = $dispretionPath . DS . Varien_File_Uploader::getNewFileName(Mage_Downloadable_Model_Link::
                                getBaseTmpPath() . DS . $linkfile);

                $ioAdapter = new Varien_Io_File();
                $ioAdapter->setAllowCreateFolders(true);
                $distanationDirectory = dirname(Mage_Downloadable_Model_Link::getBaseTmpPath() .
                        DS . $linkfile);

                try {
                    $ioAdapter->open(array('path' => $distanationDirectory));

                    $ioAdapter->cp($file, Mage_Downloadable_Model_Link::getBaseTmpPath() . DS . $linkfile);
                    $ioAdapter->chmod(Mage_Downloadable_Model_Link::getBaseTmpPath() . DS . $linkfile, 0777);
                } catch (exception $e) {
                    Mage::throwException(Mage::helper('catalog')->__('Failed to move file: %s', $e->
                                            getMessage()));
                }
                //{"file": "/2/_/2.jpg", "name": "2.jpg", "size": 23407, "status": "new"}
                $linkfile = str_replace(DS, '/', $linkfile);


                $filearrayforimports = array(array('file' => $linkfile, 'name' => $pathinfo['filename'] .
                        '.' . $pathinfo['extension'], 'status' => 'new', 'size' => filesize($file)));


                if (isset($configBundleOptionsCodes[5])) {
                    if ($configBundleOptionsCodes[5] == 0) {
                        $linkspurchasedstatus = 0;
                        $linkspurchasedstatustext = false;
                    } else {
                        $linkspurchasedstatus = 1;
                        $linkspurchasedstatustext = true;
                    }
                    $product->setLinksPurchasedSeparately($linkspurchasedstatus);
                    $product->setLinksPurchasedSeparately($linkspurchasedstatustext);
                }


                //$downloadableitems['link'][$downloadableitemsoptionscount]['link_file'] = $linkfile;
                $downloadableitems['link'][$downloadableitemsoptionscount]['file'] = Mage::
                        helper('core')->jsonEncode($filearrayforimports);
                ;
            } else
            if ($configBundleOptionsCodes[3] == "url") {
                $downloadableitems['link'][$downloadableitemsoptionscount]['link_url'] = $configBundleOptionsCodes[4];
            }
            $downloadableitems['link'][$downloadableitemsoptionscount]['sort_order'] = 0;
            $product->setDownloadableData($downloadableitems);
            $downloadableitemsoptionscount += 1;
        }
        #print_r($downloadableitems);
        //}
    }

    protected function _processBundleProduct($product, &$importData, $new) {
        if ($new) {
            $optionscount = 0;
            $items = array();
            //THIS IS FOR BUNDLE OPTIONS
            $commadelimiteddata = explode('|', $importData['bundle_options']);
            foreach ($commadelimiteddata as $data) {
                $configBundleOptionsCodes = $this->userCSVDataAsArray($data);
                $titlebundleselection = ucfirst(str_replace('_', ' ', $configBundleOptionsCodes[0]));
                $items[$optionscount]['title'] = $titlebundleselection;
                $items[$optionscount]['type'] = $configBundleOptionsCodes[1];
                $items[$optionscount]['required'] = $configBundleOptionsCodes[2];
                $items[$optionscount]['position'] = $configBundleOptionsCodes[3];
                $items[$optionscount]['delete'] = 0;
                $optionscount += 1;


                if ($items) {
                    $product->setBundleOptionsData($items);
                }
                $options_id = $product->getOptionId();
                $selections = array();
                $bundleConfigData = array();
                $optionscountselection = 0;
                //THIS IS FOR BUNDLE SELECTIONS
                $commadelimiteddataselections = explode('|', $importData['bundle_selections']);
                foreach ($commadelimiteddataselections as $selection) {
                    $configBundleSelectionCodes = $this->userCSVDataAsArray($selection);
                    $selectionscount = 0;
                    foreach ($configBundleSelectionCodes as $selectionItem) {
                        $bundleConfigData = explode(':', $selectionItem);
                        $selections[$optionscountselection][$selectionscount]['option_id'] = $options_id;
                        $selections[$optionscountselection][$selectionscount]['product_id'] = $product->
                                getIdBySku($bundleConfigData[0]);
                        $selections[$optionscountselection][$selectionscount]['selection_price_type'] =
                                $bundleConfigData[1];
                        $selections[$optionscountselection][$selectionscount]['selection_price_value'] =
                                $bundleConfigData[2];
                        $selections[$optionscountselection][$selectionscount]['is_default'] = $bundleConfigData[3];
                        if (isset($bundleConfigData) && isset($bundleConfigData[4]) && $bundleConfigData[4] !=
                                '') {
                            $selections[$optionscountselection][$selectionscount]['selection_qty'] = $bundleConfigData[4];
                            $selections[$optionscountselection][$selectionscount]['selection_can_change_qty'] =
                                    $bundleConfigData[5];
                        }
                        $selections[$optionscountselection][$selectionscount]['delete'] = 0;
                        $selectionscount += 1;
                    }
                    $optionscountselection += 1;
                }
                if ($selections) {
                    $product->setBundleSelectionsData($selections);
                }
            }


            if ($product->getPriceType() == '0') {
                $product->setCanSaveCustomOptions(true);
                if ($customOptions = $product->getProductOptions()) {
                    foreach ($customOptions as $key => $customOption) {
                        $customOptions[$key]['is_delete'] = 1;
                    }
                    $product->setProductOptions($customOptions);
                }
            }

            $product->setCanSaveBundleSelections();
        }
    }

    protected function _getValueOptions($attribute, $field, $value, $isArray) {
        if (!isset($this->__AllValueOptions[$attribute->getAttributeCode()])) {
            $options = $attribute->getSource()->getAllOptions(false);

            $_options = array();
            foreach ($options as $item) {
                $_options[$item['label']] = $item['value'];
            }

            $this->__AllValueOptions[$attribute->getAttributeCode()] = $_options;
        }

        $_options = $this->__AllValueOptions[$attribute->getAttributeCode()];



        /**
         * This is the case of Multi-Select
         */
        if ($isArray) {
            foreach ($value as $key => $subvalue) {
                if (array_key_exists($subvalue, $_options)) {
                    $setValue[] = trim($_options[$subvalue]);
                } else {
                    $newOption = $this->updateSourceAndReturnId($field, $subvalue);
                    $setValue[] = $newOption;
                    $_options[$subvalue] = $newOption;
                }
            }
        }
        /*         * This is the case of single select* */ else {
            $setValue = null;

            if (array_key_exists($value, $_options)) {
                $setValue = trim($_options[$value]);
            } else {
                $newOption = $this->updateSourceAndReturnId($field, $value);
                $setValue = $newOption;
                $_options[$value] = $newOption;
            }
        }

        return $setValue;
    }

    /* -----------------Call back function-------------------------- */

    protected function _beforeProductSave($product, $importData) {
        
    }

    protected function _afterProductLoad($product, $importData) {
        
    }

    protected function _productSave($product, $importData) {
        
    }

}
