<?php

class Mage_Catalog_Model_Convert_Adapter_Extend_ImportProducts extends
Mage_Catalog_Model_Convert_Adapter_Productimport {

    var $__defaultAttributes = array(
        'attribute_set' => 'Default',
        'qty' => '50',
        'is_in_stock' => '1',
        'visibility' => 'Not Visible Individually',
        'weight' => '1'
    );
    var $__vendorName = 'default';
    var $__skuFieldName = 'sku';
    var $__imgFieldName = 'image';
    var $__pricingFields = 'color';
    var $__categoriesFieldName = 'category';
    /**
     * Save product (import)
     *
     * @param array $importData
     * @throws Mage_Core_Exception
     * @return bool
     */
    public function saveRow(array $importData, $keepImage = false) {
        $this->__dataBeSaved = $this->__defaultAttributes;

        if (!$this->checkBeforeSave($importData)){
            return;
        }

        $this->doImageDownload($importData);
        $this->doDataFilter($importData);
        $websites = array();
        foreach (Mage::app()->getWebsites() as $web) {
            $websites[] = $web->getCode();
        }

        $this->__dataBeSaved = array_merge(
            $this->__dataBeSaved, array(
                'sku' => $importData[$this->__skuFieldName],
                'store' => "default",
                'websites' => implode(',', $websites)
            )
        );

        $importData[$this->__categoriesFieldName] = preg_replace('/:/i', '/', $importData[$this->__categoriesFieldName]);
        $importData[$this->__categoriesFieldName] = preg_replace('/\s*;\s*/i', ',', $importData[$this->__categoriesFieldName]);
        $this->__dataBeSaved["categories"] = $importData[$this->__categoriesFieldName];
        //$this->__dataBeSaved['category_ids'] = $this->catPreProcess($importData['Category IDs']) ;
        parent::saveRow($this->__dataBeSaved, $keepImage);
        return true;
    }

    protected function doImageDownload(&$data) {
        $images = array();
        $images = explode(";",$data["image"]) ;
        if (count($images)) {
            $data['image'] = array_shift($images);
            $data['gallery'] = join(',', $images);
        } else {
            $this->logErrors('[sku:' . $data[$this->__skuFieldName] . ']' . ' have no Image');
        }
    }

    protected function doDataFilter($data) {

        parent::doDataFilter($data);
        $this->convertData($data, $this->__dataBeSaved, array(
            'name' => 'name',
            'meta_keyword' => 'keyword',
            'description' => 'caption',
            'price' => 'price',
            'manufacturer' => 'brand',
            'description' => 'description',
            'short_description' => 'short_description',
            'image'             => 'image',
            'small_image'       => 'image',
            'thumbnail'         => 'image',
 	    'gallery'           => 'gallery',
            'color'             => 'color',
            'size'              => 'size',

//------------ For configurable ----------------
            'associated' => 'associated',
            'visibility' => 'visibility',
            'config_attributes' => 'config_attributes',
            'type' => 'type',
            'product_type_id' => 'product_type_id'
        ));
        // process custom option
        return $this->__dataBeSaved;
    }

}

