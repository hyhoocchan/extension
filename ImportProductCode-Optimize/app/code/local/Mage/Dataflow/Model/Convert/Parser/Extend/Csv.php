<?php

class Mage_Dataflow_Model_Convert_Parser_Extend_Csv extends Mage_Dataflow_Model_Convert_Parser_Csv {


    var $__vendorName = 'default';
    var $__skuFieldName = 'sku';
    var $__groupKeyName = 'productgroup';
    var $__nameFieldName = 'configurable_name';
    var $__optionFields = array(
        'color' => 'color',
    );

    public function parse() {

        // fixed for multibyte characters
        setlocale(LC_ALL, Mage::app()->getLocale()->getLocaleCode() . '.UTF-8');

        $fDel = $this->getVar('delimiter', ',');
        $fEnc = $this->getVar('enclose', '"');
        if ($fDel == '\t') {
            $fDel = "\t";
        }

        $adapterName = $this->getVar('adapter', null);
        $adapterMethod = $this->getVar('method', 'saveRow');

        if (!$adapterName || !$adapterMethod) {
            $message = Mage::helper('dataflow')->__('Please declare "adapter" and "method" nodes first.');
            $this->addException($message, Mage_Dataflow_Model_Convert_Exception::FATAL);
            return $this;
        }

        try {
            $adapter = Mage::getModel($adapterName);
        } catch (Exception $e) {
            $message = Mage::helper('dataflow')->__('Declared adapter %s was not found.', $adapterName);
            $this->addException($message, Mage_Dataflow_Model_Convert_Exception::FATAL);
            return $this;
        }

        if (!is_callable(array($adapter, $adapterMethod))) {
            $message = Mage::helper('dataflow')->__('Method "%s" not defined in adapter %s.', $adapterMethod, $adapterName);
            $this->addException($message, Mage_Dataflow_Model_Convert_Exception::FATAL);
            return $this;
        }

        $batchModel = $this->getBatchModel();
        $batchIoAdapter = $this->getBatchModel()->getIoAdapter();

        if (Mage::app()->getRequest()->getParam('files')) {
            $file = Mage::app()->getConfig()->getTempVarDir() . '/import/'
                . urldecode(Mage::app()->getRequest()->getParam('files'));
            $this->_copy($file);
        }

        $batchIoAdapter->open(false);

        $isFieldNames = $this->getVar('fieldnames', '') == 'true' ? true : false;
        if (!$isFieldNames && is_array($this->getVar('map'))) {
            $fieldNames = $this->getVar('map');
        } else {
            $fieldNames = array();
            foreach ($batchIoAdapter->read(true, $fDel, $fEnc) as $v) {
                $v = trim($v);
                $fieldNames[$v] = $v;
            }
        }

        $countRows = 0;

        $__current_position = 0;

        $groupItems = array();
        $itemConf = null;
        $attrOptions = $this->getDefaultOptions();
        $sameProdGroup = false;

        while (($csvData = $batchIoAdapter->read(true, $fDel, $fEnc)) !== false) {
            if (count($csvData) == 1 && $csvData[0] === null) {
                continue;
            }

            $__current_position++;
            $itemData = array('__current_position' => $__current_position);
            $i = 0;
            foreach ($fieldNames as $field) {
                $itemData[$field] = isset($csvData[$i]) ? $csvData[$i] : null;
                $i++;
            }

            $sku = $itemData[$this->__skuFieldName];
            $lastGroup = $itemData[$this->__groupKeyName];

            if (empty($lastGroup)) {
                if (!empty($itemConf)) {
                    $itemConf['__current_position'] = ($__current_position - 1) . '+';
                    $itemConf = $this->determineConfigAttrs($itemConf, $attrOptions);

                    if ($itemConf) {
                        $groupItems[] = $itemConf;
                        $countRows++;
                    } else
                        $this->changeVisibility($groupItems);

                    //print_r($groupItems);
                    $this->saveGroupBatchItems($groupItems);

                    unset($attrOptions, $groupItems);
                    $attrOptions = $this->getDefaultOptions();
                    $groupItems = array();
                    $itemConf = null;
                }

                $itemData['visibility'] = 'Catalog, Search';
                $this->saveBatchItem($itemData);
            }else {
                if (empty($itemConf)) {
                    $itemConf = $this->setConfigurableProduct($itemData);
                } else {
                    if ($itemConf[$this->__groupKeyName] == $lastGroup) {
                        $itemConf['associated'][] = $sku;
                    } else {
                        $itemConf['__current_position'] = ($__current_position - 1) . '+';
                        $itemConf = $this->determineConfigAttrs($itemConf, $attrOptions);

                        if ($itemConf) {
                            $groupItems[] = $itemConf;
                            $countRows++;
                        } else
                            $this->changeVisibility($groupItems);

                        //print_r($groupItems);
                        $this->saveGroupBatchItems($groupItems);

                        unset($attrOptions, $groupItems);
                        $attrOptions = $this->getDefaultOptions();
                        $groupItems = array();
                        $itemConf = $this->setConfigurableProduct($itemData);
                        //$sameProdGroup = true;
                    }
                }

                foreach ($this->__optionFields as $op => $ch) {
                    $attrValue = trim($itemData[$ch]);
                    if (!empty($attrValue)) {
                        $attrOptions[$op][] = null;
                    }
                }

                $groupItems[] = $itemData;
            }

            $countRows++;
        }

        if (!empty($itemConf)) {
            $itemConf['__current_position'] = ($__current_position - 1) . '+';
            $itemConf = $this->determineConfigAttrs($itemConf, $attrOptions);

            if ($itemConf) {
                $groupItems[] = $itemConf;
                $countRows++;
            }else
                $this->changeVisibility($groupItems);
//print_r($groupItems);                
            $this->saveGroupBatchItems($groupItems);
        }
//exit;
        $this->addException(Mage::helper('dataflow')->__('Found %d rows.', $countRows));
        $this->addException(Mage::helper('dataflow')->__('Starting %s :: %s', $adapterName, $adapterMethod));
        $batchModel->setParams($this->getVars())
            ->setAdapter($adapterName)
            ->save();
        //$adapter->$adapterMethod();
        return $this;
    }

    protected function setConfigurableProduct(array $importData) {

        $sku = $importData[$this->__skuFieldName];
        $return_data = $importData;
        $return_data['associated'][] = $sku;
        $return_data[$this->__skuFieldName] = 'configurable_' . $importData[$this->__groupKeyName];
        $return_data['visibility'] = 'Catalog, Search';
        $return_data['type'] = 'configurable';
        $return_data['name'] = $importData[$this->__nameFieldName];
        $return_data['product_type_id'] = 'configurable';
        return $return_data;
    }

    protected function determineConfigAttrs($data, $attrOptions) {

        $returnData = $data;

        foreach ($attrOptions as $op => $ch) {
            if (count($ch) > 0)
                $returnData['config_attributes'][] = $op;
        }

        if (count($returnData['config_attributes']) > 0) {
            $returnData['config_attributes'] = join(',', $returnData['config_attributes']);
            $returnData['associated'] = join(',', $returnData['associated']);
        } else {
            return false;
        }

        //unset($returnData['__options']); 
        return $returnData;
    }

    protected function saveBatchItem($item) {

        $batchImportModel = $this->getBatchImportModel()
            ->setId(null)
            ->setBatchId($this->getBatchModel()->getId())
            ->setBatchData($item)
            ->setStatus(1)
            ->save();
    }

    protected function saveGroupBatchItems($items) {

        foreach ($items as $item) {
            $batchImportModel = $this->getBatchImportModel()
                ->setId(null)
                ->setBatchId($this->getBatchModel()->getId())
                ->setBatchData($item)
                ->setStatus(1)
                ->save();
        }
    }

    protected function isEqOptions($options1, $options2) {

        if (count($options1) != count($options2))
            return false;
        else {
            foreach ($options1 as $op) {
                if (!in_array($op, $options2)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function getDefaultOptions() {

        if (!isset($this->__defaultOptions)) {
            $this->__defaultOptions = array();
            foreach ($this->__optionFields as $k => $v) {
                $this->__defaultOptions[$k] = array();
            }
        }

        return $this->__defaultOptions;
    }

    protected function changeVisibility(&$items) {

        foreach ($items as &$item) {
            $item['visibility'] = 'Catalog, Search';
        }
    }

}
