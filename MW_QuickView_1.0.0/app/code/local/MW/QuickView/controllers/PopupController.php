<?php
require 'Mage/Catalog/controllers/ProductController.php';
class MW_QuickView_PopupController extends Mage_Catalog_ProductController
{
    public function viewAction()
    {
        return parent::viewAction();
    }

    public function productoptionAction()
    {
        return parent::viewAction();
    }
}
