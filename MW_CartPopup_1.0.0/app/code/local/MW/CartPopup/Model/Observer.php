<?php

class MW_CartPopup_Model_Observer
{
    //array('action'=>$this, 'layout'=>$this->getLayout())
    public function setLayoutForCartPopup($ob)
    {
        /* @var $ctr Mage_Checkout_CartController */
        $ctr = $ob->getEvent()->getAction();
        if ($ctr->getRequest()->getParam(MW_CartPopup_Model_Response::AJAX_REQUEST_KEY) == true && $this->isCheckCartIndex($ctr)) {
            /* @var $layout Mage_Core_Model_Layout */
            $layout = $ob->getEvent()->getLayout();
            $layout->getBlock("checkout.cart")
                ->setTemplate("mw/cartpopup/cartview.phtml")
                ->addItemRender("simple","checkout/cart_item_renderer","mw/cartpopup/item/default.phtml")
                ->addItemRender("grouped","checkout/cart_item_renderer_grouped","mw/cartpopup/item/default.phtml")
                ->addItemRender("configurable","checkout/cart_item_renderer_configurable","mw/cartpopup/item/default.phtml")
            ;
            /*
             *
             <action method="addItemRender"><type>simple</type><block>checkout/cart_item_renderer</block><template>checkout/cart/item/default.phtml</template></action>
                <action method="addItemRender"><type>grouped</type><block>checkout/cart_item_renderer_grouped</block><template>checkout/cart/item/default.phtml</template></action>
                <action method="addItemRender"><type>configurable</type><block>checkout/cart_item_renderer_configurable</block><template>checkout/cart/item/default.phtml</template></action>
             */
        }
    }

    private function isCheckCartIndex(Mage_Checkout_CartController $ctr){
        $controllerName = $ctr->getRequest()->getControllerName();
        $moduleName = $ctr->getRequest()->getModuleName();
        $actionName = $ctr->getRequest()->getActionName();
        return ($controllerName == "cart" && $moduleName == "checkout" && $actionName == "index");
    }

    public function postdispatchOpenCartPopup($ob){
        /* @var $ctr Mage_Checkout_CartController */
        $ctr = $ob->getEvent()->getControllerAction();
        if ($ctr->getRequest()->getParam(MW_CartPopup_Model_Response::AJAX_REQUEST_KEY) == true && $this->isCheckCartIndex($ctr)) {
            echo $ctr->getLayout()->getBlock("checkout.cart")->toHtml();
            die;
        }
    }
}