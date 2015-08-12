<?php
/**
* @category   MW
* @package    MW_ConfigurableOptionThumbnail
* @version    1.0.0
* @copyright  Copyright (c) 2012 Magento Whiz. (http://www.magentowhiz.com)
*/
class MW_ConfigurableOptionThumbnail_Block_ConfigurableOptionThumbnail extends Mage_Catalog_Block_Product_View {

    private $_attrShowImage = array();
    private $_productAttr = array();

    public function _construct() {
        parent::_construct();
        $this->_attrShowImage = Mage::helper('configurableoptionthumbnail')->getAttributeShowImage();
        $this->_productAttr = $this->getArrAtributeCode();
        $this->atributeFillter();
    }

    private function atributeFillter() {
        foreach ($this->_attrShowImage as $key => $item) {
            if (!in_array($item, $this->_productAttr)) {
                unset($this->_attrShowImage[$key]);
            }
        }
        $result = array();
        foreach ($this->_productAttr as $key => $item) {
            if (in_array($item, $this->_attrShowImage)) {
                $result[$key] = $item;
            }
        }
        $this->_attrShowImage = $result;
    }

    public function getThumbnailGalleryData() {
        if(!$this->hasData('thumbnail_gallery_data')){
            $data = $this->getCompleteAttrInfo();
            foreach ($data as $key=>$item){
                $data[$key]['image_url'] = (string)$this->helper('catalog/image')->init(Mage::getModel('catalog/product')->load($key), 'image');
            }
            $this->setData('thumbnail_gallery_data',$data);
        }            
        return $this->getData('thumbnail_gallery_data');
    }

    public function getThumbnailGalleryJsonData(){
        return Mage::helper('core')->jsonEncode($this->getThumbnailGalleryData());
    }
    
    /*
     * 
     * @return array Configurable Product Attribute Code
     */

    private function getArrAtributeCode() {
        if (!$this->getProduct()->isConfigurable()) {
            throw new Exception('Must is Configurable product.');
        }
        $result = array();
        $attrs = $this->getProduct()->getTypeInstance(true)->getConfigurableAttributesAsArray($this->getProduct());
        foreach ($attrs as $item) {
            $result[$item['attribute_id']] = $item['attribute_code'];
        }
        return $result;
    }

    public function getCompleteAttrInfo() {
        $className = Mage::getConfig()->getBlockClassName('catalog/product_view_type_configurable');
        $block = new $className();
        $config = Mage::helper('core')->jsonDecode($block->getJsonConfig());
        /*
         * Unset Attribute not in BE config
         */
        $tmpAttr = $config['attributes'];
        foreach ($tmpAttr as $key => $item) {
            if (!in_array($key, array_keys($this->_attrShowImage))) {
                unset($tmpAttr[$key]);
            }
        }
        $product = array();
        foreach ($tmpAttr as $key => $item) {
            foreach ($item['options'] as $itemOption) {
                foreach ($itemOption['products'] as $p) {
                    if (!is_array($product[$p])) {
                        $product[$p] = array();
                    }
                    $product[$p]['of_option'][$item['id']] = $itemOption['id'];
                }
            }
        }

        $this->processFillterProduct($product);

        return $product;
    }

    private function processFillterProduct(&$prouctArr) {
        // Remove product don't have image
        foreach ($prouctArr as $key => $i) {
            $tmpProduct = Mage::getModel("catalog/product")->load($key);
            if ($tmpProduct->getImage() == 'no_selection') {
                unset($prouctArr[$key]);
            }
        }
        //---------------------------------------------------------
        $i = 0;
        foreach ($prouctArr as $key => $item) {
            if ($i == count($prouctArr) - 1)
                break;
            $j = -1;
            $isNotLike = true;
            foreach ($prouctArr as $tmpItem) {
                $j++;
                if ($j < $i + 1)
                    continue;
                $tmpResult = array_diff_assoc($item['of_option'], $tmpItem['of_option']);
                if (count($tmpResult) == 0) {
                    $isNotLike = false;
                    unset($prouctArr[$key]);
                }
            }
            if ($isNotLike) {
                $i++;
            }
        }
    }

}