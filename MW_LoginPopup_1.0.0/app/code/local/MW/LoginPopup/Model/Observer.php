<?php
class MW_LoginPopup_Model_Observer
{
    function loginPopupHookLogin($ob)
    {
        /* @var $request Mage_Core_Controller_Request_Http */
        $request = $ob->getEvent()->getControllerAction()->getRequest();
        if($request->getParam("is_mw_login_process") == 1){
            /* @var $customSession Mage_Customer_Model_Session */
            $customSession = Mage::getModel("customer/session");

            $collect = Mage::getSingleton("customer/session")->getMessages(true)->getItems();
            $messages = array();
            foreach($collect as $e){
                /* @var $e Mage_Core_Model_Message_Error */
                $messages[] = $e->getText();
            }
            $success = (bool)$customSession->isLoggedIn();
            $response = array(
                "wishlist_total_item" => $customSession->getWishlistItemCount(),
                "customer"            => $customSession->getCustomer()->getData(),
                "messages"            => $messages,
                "success"             => $success
            );

            echo Mage::helper("core")->jsonEncode($response);
            die;
        }
    }
}