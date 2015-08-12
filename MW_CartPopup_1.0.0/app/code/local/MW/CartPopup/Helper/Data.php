<?php

class MW_CartPopup_Helper_Data extends Mage_Core_Helper_Abstract {
    public function getDeleteUrl($item)
    {
        return Mage::getUrl(
            'checkout/cart/delete',
            array(
                'id'=>$item->getId(),
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl(Mage::getUrl("checkout/cart/index"))
            )
        );
    }
}