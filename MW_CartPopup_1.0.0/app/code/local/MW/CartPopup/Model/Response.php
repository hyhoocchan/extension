<?php

class MW_CartPopup_Model_Response extends Varien_Object
{
    const AJAX_REQUEST_KEY = "mw_cartpopup_is_ajax";
    /**
     * @param bool $flag
     */
    public function setContent($content){
        $this->setData("content",$content);
        return $this;
    }

    public function send(){
        Mage::app()->getFrontController()->getResponse()->setBody(Mage::helper("core")->jsonEncode($this->getData()));
    }
}