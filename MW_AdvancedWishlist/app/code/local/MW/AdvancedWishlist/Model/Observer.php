<?php
class MW_AdvancedWishlist_Model_Observer
{
    public function predispatchWishlistAdd($ob)
    {
        if (Mage::app()->getRequest()->getParam("ajax_add_to_wishlist") == true) {
            if(!Mage::getModel("customer/session")->isLoggedIn()){
                $response = array(
                    "success" => false,
                    "messages" => array("Must be login !")
                );
                echo Mage::helper("core")->jsonEncode($response);
                die;
            }
        }
    }

    /*
     * array(
                    'wishlist'  => $wishlist,
                    'product'   => $product,
                    'item'      => $result
                )
     */
    public function addWishlistDetect($ob){
        if (Mage::app()->getRequest()->getParam("ajax_add_to_wishlist") == true) {
//            echo get_class($ob->getEvent()->getWishlist());
//            echo get_class($ob->getEvent()->getProduct());
//            echo get_class($ob->getEvent()->getItem());
//            die();
//            Mage_Wishlist_Model_WishlistMage_Catalog_Model_ProductMage_Wishlist_Model_Item
//            $wishlist = $ob->getEvent()->getWishlist();
//            $product = $ob->getEvent()->getProduct();
//            $item = $ob->getEvent()->getItem();

            Mage::register("mw_advanced_add_to_wishlist_success_data",$ob->getEvent());
        }
    }

    public function postdispatchWishlistAdd($ob){
        if (Mage::app()->getRequest()->getParam("ajax_add_to_wishlist") == true) {
            /* @var $aaa Mage_Core_Model_Message_Collection */
            $collect = Mage::getSingleton("customer/session")->getMessages(true)->getItems();
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

//            $ctr->loadLayout();
            $sucessData = Mage::registry("mw_advanced_add_to_wishlist_success_data");
            if($sucessData){
                $response['success'] = true;
                $response['product'] = $sucessData->getProduct()->getData();
                $response['wishlist'] = $sucessData->getWishlist()->getData();
                $response['item'] = $sucessData->getItem()->getData();

                $response['html']    = $ctr->getLayout()
                    ->createBlock("core/template","advancedwishlist.popup_one_product")
                    ->setTemplate("mw/advancedwishlist/popup_one_product.phtml")
                    ->setProduct($sucessData->getProduct())
                    ->toHtml();
//                $response['top_html'] = $ctr->getLayout()->getBlock("global.compare")->toHtml();
            }else{
                $response['success'] = false;
            }
            $response["total_item"] = $sucessData->getWishlist()->getItemsCount();
            echo Mage::helper("core")->jsonEncode($response);
            die;
        }
    }
}