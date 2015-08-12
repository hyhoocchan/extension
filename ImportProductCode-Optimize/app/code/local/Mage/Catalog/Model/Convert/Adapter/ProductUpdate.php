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

class Mage_Catalog_Model_Convert_Adapter_ProductUpdate extends
    Mage_Catalog_Model_Convert_Adapter_Productimport
{
    
    var $__productAttributes = array('sku', 'has_options', 'required_options');
    var $__multiValue = array(
        'status' => array(
            '1' => 'Enabled',
            '2' => 'Disabled',
            '3' => 'Deleted'
        )
    );

    /**
     * Save product (import)
     *
     * @param array $importData
     * @throws Mage_Core_Exception
     * @return bool
     */        
    public function saveRow(array $importData, $keepImage=false)
    {
        $product = $this->getProductModel()->reset();
        
        if (empty($importData['store'])) {
            if (!is_null($this->getBatchParams('store'))) {
                $store = $this->getStoreById($this->getBatchParams('store'));
            } else {
                $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" not defined',
                    'store');
                $this->logErrors($message);
                Mage::throwException($message);
            }
        } else {
            $store = $this->getStoreByCode($importData['store']);
        }

        if ($store === false) {
            $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, store "%s" field not exists',
                $importData['store']);
            $this->logErrors($message);
            Mage::throwException($message);
        }

        if (empty($importData['sku'])) {
            $message = Mage::helper('catalog')->__($rowInfo . 'Skip import row, required field "%s" not defined',
                'sku');
            $this->logErrors($message);
            Mage::throwException($message);
        }
        
        $this->_beforeLoadProduct($importData);
        
        $importData['sku'] = substr($importData['sku'], 0, 64);
        $productId = $product->getIdBySku($importData['sku']);
        
        //print_r($importData);
        
        if (!$productId){
            $this->_notExistProduct($importData);
            return;
        }
        
        $product->setId($productId);
        $product->setStoreId($store->getId());
        
        $this->_afterLoadProduct($importData, $productId);
        
        unset($importData['sku']);
        
        $productModel = Mage::getSingleton('catalog/import_product_action');
        $data = $productModel->getAtrributeData($importData);
        

        if($logFields = $this->getInventoryLogFieldNames())
        {
            if(!isset($this->__logFieldData))
            {
                $logFieldsKey = array_keys($logFields);
                $this->__logFieldData = $productModel->getAtrributeData(array_combine($logFieldsKey, $logFieldsKey));
            }

            $productModel->simpleLoadProduct($this->__logFieldData, $product);
        }
        else
        {
            $productModel->simpleLoadProduct(array('attributes' => array(), 'inventories' => array()), $product);
        }
        
        $this->_beforeUpdateProduct($data, $importData, $product);
        
        
        
        $productModel->update($productId, $data, $store->getId(), $product);
        $this->_afterUpdateProduct($data, $importData, $product);
        
        if($logFields)
        {
            $product->setOrigData();
            $productModel->simpleLoadProduct($this->__logFieldData, $product);
            $this->writeUpdateLog($product, $logFields);
        }
    }
    

    protected function _getValueOptions($attribute, $field, $value, $isArray)
    {
        if(! isset($this->__AllValueOptions[$attribute->getAttributeCode()]))
        {
            $options = $attribute->getSource()->getAllOptions(false);
        
            $_options = array();
            foreach($options as $item)
            {
                $_options[$item['label']] = $item['value'];
            }
            
            $this->__AllValueOptions[$attribute->getAttributeCode()] = $_options;            
        }
        
        $_options = $this->__AllValueOptions[$attribute->getAttributeCode()];
        
    
        
        /**
         * This is the case of Multi-Select
         */
        if ($isArray) {
        	foreach($value as $key=>$subvalue){
                if(array_key_exists($subvalue, $_options))
                {
                    $setValue[] = trim($_options[$subvalue]);
                }
                else
                {
                    $newOption = $this->updateSourceAndReturnId($field,$subvalue);
                    $setValue[] = $newOption;
                    $_options[$subvalue] = $newOption;
                }
        	}
        
        }
        /**This is the case of single select**/
        else {
        	$setValue = null;
            
            if(array_key_exists($value, $_options))
            {
                $setValue = trim($_options[$value]);
            }
            else
            {
                $newOption = $this->updateSourceAndReturnId($field,$value);
                $setValue = $newOption;
                $_options[$value] = $newOption;
            }
        }
        
        return $setValue;
    }
    
    public function getInventoryLogFieldNames()
    {
        if(!isset($this->__inventoryLogFieldNames))
        {
            $batchModel = Mage::getSingleton('dataflow/batch');
            $params = $batchModel->getParams();
            
            if(empty($params['log_fieldnames']))
            {
                $this->__inventoryLogFieldNames = false;
                $this->__inventoryLogFieldNames; 
            }
            
            $fieldnames = $params['log_fieldnames'];
            
            $fieldnames = explode(',', $fieldnames);
            $fns = array();
            foreach($fieldnames as $fn)
            {
                $fnd = explode('::', $fn);
                $fns[trim($fnd[0])] = count($fnd)>1 ? trim($fnd[1]) : trim($fnd[0]);
            }
            $this->__inventoryLogFieldNames = $fns;
        }
        return $this->__inventoryLogFieldNames;
    }
    
    public function finish()
    {
        parent::finish();
        
        $batchModel = Mage::getSingleton('dataflow/batch');
        $params = $batchModel->getParams();
        
        $filename = 'inventory' . $this->getRunDate() . '.csv';
        $path = Mage::getBaseDir('var').DS.'log'.DS. 'import' . DS . $this->__vendorName . DS .'inventory';
        if(file_exists($path . DS . $filename))
        {
            
            if(!empty($params['log_path']))
            {
                $resource = $this->getInventoryLogResource();
                $resource->write($filename, $path . DS . $filename);    
            }
            if(!empty($params['log_email']))
            {
                $this->sendEmail('Attached is the inventory update reporting or download <a href="{file}">here</a>', $path . DS . $filename, $filename);
            }
            
            @unlink($path . DS . $filename);
        }
        else
        {
            if(!empty($params['log_email']))
            {
                $this->sendEmail('There is nothing that can be changed');
            }
        }
        
        //$batchId = $batchModel->getId();
        //Mage::getSingleton('core/session')->unsetData('import_date_'. $batchId);
    }
    
    
    
    protected function getRunDate(){
        if(!isset($this->__runDate))
        {
            $batchModel = Mage::getSingleton('dataflow/batch');
            
            $this->__runDate = date('Ymd_His', strtotime($batchModel->getData('created_at')));
        }
        return $this->__runDate;
    }
    
    protected function inventoryLog($row){
        $filename = 'inventory' . $this->getRunDate() . '.csv';
        $path = Mage::getBaseDir('var').DS.'log'.DS. 'import' . DS . $this->__vendorName . DS .'inventory';
        
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        
        if(!file_exists($path . DS . $filename))
        {
            $fieldnames = $this->getInventoryLogFieldNames();
            $header = array('sku');
            
            foreach($fieldnames as $field)
            {
                $header[] = 'Old '. $field;
                $header[] = 'New '. $field;
            }
            
            $header[] = 'Import status';
            
            $log = fopen($path . DS . $filename, 'a');
            fputcsv($log, $header,',');
            fclose($log);
        }
        
        $log = fopen($path . DS . $filename, 'a');
        fputcsv($log, $row,',');
        fclose($log);
    }
    
    public function getInventoryLogResource()
    {
        $forWrite = true;
        if (!isset($this->_bk_resource)) {
            
            $batchModel = Mage::getSingleton('dataflow/batch');
            
            $ioConfig = $batchModel->getParams();
            $ioBkConfig = array();
            foreach($ioConfig as $k => $v){
                if (preg_match('/\Alog_/i', $k)) {
                	$ioBkConfig[substr($k, strlen('log_'))] = $v;
                }
            }
            $ioConfig = $ioBkConfig;
            $convertAdapterIo = Mage::getModel('IKT_OrderExport_Model_ConvertAdapterIo');
            $this->_bk_resource = $convertAdapterIo->__getResource('inventory', $ioConfig, $forWrite);;
        }
        return $this->_bk_resource;
    }
    
    public function sendEmail($emailBody, $dataFile = null, $filename = 'inventory update')
    {
        $model = Mage::getModel('IKT_OrderExport_Model_ConvertAdapterOrder');
        $batchModel = Mage::getSingleton('dataflow/batch');
        $params =  $batchModel->getParams();
        
        $mimetype = 'text/csv';
        
        
        $type = isset($params['log_type']) ? $params['log_type'] : 'file' ;
        
        $filePath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $params['log_path'] . '/' . $filename;
        $pathFile = $params['log_path'];

        if($type !== 'file'){
            $filePath = $type . '://' . $params['log_host'] . $this->getInventoryLogResource()->pwd() . '/' . $filename;
        }
        

        $emailBody = str_replace('{file}', $filePath, $emailBody);

        //------------- End by forix ------------------------------
        
        $model->sendNotificationEmail($params['log_email'],
                                  'Patron store: update inventory ' . $this->__vendorName . ' ' . $this->getRunDate(),
                                  $emailBody,
                                  $pathFile,
                                  $filename, $mimetype, $dataFile);
    }
    
    
    protected function doDataFilter($data){
        $this->__dataBeSaved = array();
        return $this->__dataBeSaved;
    }
    
    
    protected function writeUpdateLog($object, $logFields, $isNew = false)
    {
        //if($object->getTypeId() == 'configurable') return;
        
        //print_r($object->getData());
        //print_r($object->getOrigData());
        
        $row = array();
        $isWrite = false;
        foreach($logFields as $fn=>$fnv)
        {
            if($object->hasData($fn) && ($object->getData($fn) != $object->getOrigData($fn)))
            {
                if($fn == 'qty')
                {
                    $row[] = preg_replace('/\.0+\z/i', '', $object->getOrigData($fn));
                    $row[] = preg_replace('/\.0+\z/i', '', $object->getData($fn));
                }
                elseif(array_key_exists($fn, $this->__multiValue))
                {
                    $row[] = '' . $this->__multiValue[$fn][$object->getOrigData($fn)];
                    $row[] = '' . $this->__multiValue[$fn][$object->getData($fn)];
                }
                else
                {
                    $row[] = '' . $object->getOrigData($fn);
                    $row[] = '' . $object->getData($fn);                    
                }
                $isWrite = true;
            }
            else
            {
                $row[] = ''; $row[] = '';
            }
        }
        
        if($isWrite || $isNew)
        {
            array_unshift($row, $object->getData('sku'));
            if($isNew === -1)
                $row[] = 'Deleted';
            elseif($isNew)
                $row[] = 'Add new';
            else
                $row[] = 'Update';
            $this->inventoryLog($row);
        }
    }
    
    
    
    //Callback function
    protected function _beforeLoadProduct(array &$importData){}
    protected function _afterLoadProduct(array &$importData, $productId){}
    protected function _beforeUpdateProduct(array &$data, array $importData, $product)
    {
        if($product->getTypeId() == 'configurable') return;
        if(isset($data['inventories']['qty']))
        {
            if($data['inventories']['qty'] > 0)
            {
                $data['attributes']['status'] = '1';
                $data['inventories']['is_in_stock'] = '1';
            }else
            {
                $data['attributes']['status'] = '2';
                $data['inventories']['is_in_stock'] = '0';
            }
            $data['inventories']['use_config_manage_stock'] = '1';
        }
        
        //var_dump($data);
        //var_dump("<br/>");
        if (isset($data['inventories'])) {
        }
    }
    protected function _afterUpdateProduct(array &$data, array $importData, $product){}
    
    protected function _notExistProduct(array &$importData){}
}