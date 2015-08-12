<?php
/**
* @category   MW
* @package    MW_ConfigurableOptionThumbnail
* @version    1.0.0
* @copyright  Copyright (c) 2012 Magento Whiz. (http://www.magentowhiz.com)
*/
class MW_ConfigurableOptionThumbnail_Helper_Data extends Mage_Core_Helper_Abstract
{   
    public function getAttributeShowImage()
    {
        $result = preg_split('/\r?\n\r?/i', trim(Mage::getStoreConfig('configurableoptionthumbnail_options/genaral/attribute_show_image')));       
        for($i=0,$c=count($result);$i<$c;$i++){
            $result[$i] = trim(str_replace("\r", '', $result[$i])); 
        }        
        return $result;
    }
}