<?php
/**
 * @category   MW
 * @package    MW_CartAjax
 * @version    1.0.0
 * @copyright  Copyright (c) 2012 Magento Whiz. (http://www.magentowhiz.com)
 */

class MW_CartAjax_Model_Observer
{
    /**
     * array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()
     */
    public function addToCartAjax($ob)
    {
        if ($ob->getEvent()->getRequest()->getParam(MW_CartAjax_Model_Response::AJAX_REQUEST_KEY) == true) {
            Mage::getSingleton("checkout/session")->setNoCartRedirect(true);
            Mage::getSingleton("cartajax/response")
                ->setMessages(Mage::helper('checkout')->__("This item was added to your cart."))
                ->setCartTotalQty((int)Mage::getSingleton('checkout/session')->getQuote()->getItemsQty())
                ->setSuccess(true)
                ->setProduct($ob->getEvent()->getProduct()->toArray())
                ->send();
        }
    }

    public function postdispatchToCartAjax($ob)
    {
        if (Mage::app()->getRequest()->getParam(MW_CartAjax_Model_Response::AJAX_REQUEST_KEY) == true) {
            $response = Mage::getSingleton("cartajax/response");
            if($response->getSuccess()) return;

            /* @var $aaa Mage_Core_Model_Message_Collection */

            $collect = Mage::getSingleton("checkout/session")->getMessages(true)->getItems();
            $messages = array();
            foreach($collect as $e){
                /* @var $e Mage_Core_Model_Message_Error */
                $messages[] = $e->getText();
            }

            $productId = (int) Mage::app()->getRequest()->getParam('product');
            if ($productId) {
                /* @var $product Mage_Catalog_Model_Product */
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);
            }

            echo $response
                ->setMessages($messages)
                ->setCartTotalQty((int)Mage::getSingleton('checkout/session')->getQuote()->getItemsQty())
                ->setSuccess(false)
                ->setProduct($product->toArray())
                ->toJson();
            die;
        }
    }
}