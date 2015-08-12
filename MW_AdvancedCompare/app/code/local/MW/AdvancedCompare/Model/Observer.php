<?php
/**
 * @category   MW
 * @package    MW_CartAjax
 * @version    1.0.0
 * @copyright  Copyright (c) 2012 Magento Whiz. (http://www.magentowhiz.com)
 */

class MW_AdvancedCompare_Model_Observer
{
    public function postdispatchAddToCompare($ob)
    {
        if (Mage::app()->getRequest()->getParam("ajax_add_to_compare") == true) {
            /* @var $aaa Mage_Core_Model_Message_Collection */
            $collect = Mage::getSingleton("catalog/session")->getMessages(true)->getItems();
            $messages = array();
            foreach($collect as $e){
                /* @var $e Mage_Core_Model_Message_Error */
                $messages[] = $e->getText();
            }
            $response = array(
                "messages" => $messages
            );
            /* @var $ctr Mage_Catalog_Product_CompareController */
            $ctr = $ob->getEvent()->getControllerAction();

            $ctr->loadLayout();
            $sucessData = Mage::registry("mw_advanced_add_to_compare_success_data");
            if($sucessData){
                $response['success'] = true;
                $response['product'] = $sucessData["product"]->getData();
            }else{
                $response['success'] = false;
            }
            $response["total_item"] = Mage::helper("catalog/product_compare")->getItemCount();
            echo Mage::helper("core")->jsonEncode($response);
            die;
        }
    }

    public function detectAddToCompare($ob){
        $product = $ob->getEvent()->getProduct();
        if($product->getId()){
            Mage::register("mw_advanced_add_to_compare_success_data",array(
                'product' => $product->load()
            ));
        }
    }
}