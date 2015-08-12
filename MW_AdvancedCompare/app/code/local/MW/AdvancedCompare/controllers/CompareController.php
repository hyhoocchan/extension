<?php

class MW_AdvancedCompare_CompareController extends Mage_Core_Controller_Front_Action
{
    public function popupAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->getBlock("advancedcompare.popup")->toHtml());
    }

    public function reloadCompareRelatedAreaAction()
    {
        $this->loadLayout("catalog_category_layered");
        $blockLeft = $this->getLayout()->getBlock("catalog.compare.sidebar");
        if ($blockLeft) $blockLeft = $blockLeft->toHtml();
        $response = array(
            "left_block" => $blockLeft
        );
        $this->getResponse()->setBody(Mage::helper("core")->jsonEncode($response));
    }
}
