<?php
/**
 * @category   MW
 * @package    MW_CartAjax
 * @version    1.0.0
 * @copyright  Copyright (c) 2012 Magento Whiz. (http://www.magentowhiz.com)
 */

class MW_CartAjax_Model_Response extends Varien_Object
{

    const AJAX_REQUEST_KEY = "mw_cartajax_is_ajax";
    /**
     * @param bool $flag
     */
    public function setSuccess($flag){
        $this->setData("success",$flag);
        return $this;
    }

    /**
     * @param $qty
     * @return MW_Cartajax_Model_Response
     */
    public function setCartTotalQty($qty){
        $this->setData("cart_total_qty",$qty);
        return $this;
    }

    /**
     * @param $mes
     * @return MW_Cartajax_Model_Response
     */
    public function setMessages($mes){
        $mes = is_string($mes) ? array($mes):$mes;
        $this->setData("messages",$mes);
        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return MW_Cartajax_Model_Response
     */
    public function setProduct(Mage_Catalog_Model_Product $product){
        $this->setData("product",$product);
        return $this;
    }

    /**
     * Send data to response object of magento
     */
    public function send(){
        Mage::app()->getFrontController()->getResponse()->setBody(Mage::helper("core")->jsonEncode($this->getData()));
    }

}