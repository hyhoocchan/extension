<?php

/**
 * @copyright Copyright (c) 2012 MagentoWhiz.
 * @package MW_AdvancedInvoicePrinting
 */
class MW_AdvancedInvoicePrinting_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice {

    const FOOTER_TEXT_LINE_HEIGHT = 12;

    public function newPage(array $settings = array()) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::newPage($settings);
        }
        /* Add new table head */
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->drawWaterMask($page);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        return $page;
    }

    /**
     * Insert address to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param null $store
     * Is insert header text
     */
    protected function insertAddress(&$page, $store = null) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::insertAddress($page, $store);
        }
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page, 10);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 800;
        $top = 800;
        foreach (explode("\n", Mage::getStoreConfig('advancedinvoiceprinting_options/general/headertext', $store)) as $value) {
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(trim(strip_tags($_value)), $this->getAlignRight($_value, 130, 440, $font, 10), $top, 'UTF-8');
                    $top -= 10;
                }
            }
        }
        $this->y = ($this->y > $top) ? $top : $this->y;
    }

    protected function insertLogo(&$page, $store = null) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::insertLogo($page, $store);
        }
        try {
            $this->y = $this->y ? $this->y : 800;
            $image = Mage::getStoreConfig('advancedinvoiceprinting_options/general/logo', $store);
            if ($image) {
                $image = Mage::getBaseDir('media') . '/sales/advancedinvoiceprinting/logo/' . $image;
                if (is_file($image)) {
                    $image = Zend_Pdf_Image::imageWithPath($image);
                    $top = 810; //top border of the page

                    $percent = Mage::getStoreConfig('advancedinvoiceprinting_options/general/logo_size', $store);
                    $width = $image->getPixelWidth();
                    $height = $image->getPixelHeight();
                    $widthLimit = $width * $percent / 100;
                    $heightLimit = $height * $percent / 100;
                    //preserving aspect ratio (proportions)
                    $ratio = $width / $height;
                    if ($ratio > 1 && $width > $widthLimit) {
                        $width = $widthLimit;
                        $height = $width / $ratio;
                    } elseif ($ratio < 1 && $height > $heightLimit) {
                        $height = $heightLimit;
                        $width = $height * $ratio;
                    } elseif ($ratio == 1 && $height > $heightLimit) {
                        $height = $heightLimit;
                        $width = $widthLimit;
                    }

                    $y1 = $top - $height;
                    $y2 = $top;
                    $x1 = 25;
                    $x2 = $x1 + $width;
                    //coordinates after transformation are rounded by Zend
                    $page->drawImage($image, $x1, $y1, $x2, $y2);

                    $this->y = $y1 - 10;
                }
            }
        } catch (Exception $e) {
            $this->_setFontRegular($page, 10);
            $page->drawText($e->getMessage(), 25, 820, 'UTF-8');
        }
    }

    /**
     *
     * @param Zend_Pdf_Page $page 
     * @return void Process drawImage on PDF page
     */
    protected function drawWaterMask(&$page) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return;
        }
        $pageWidth = $page->getWidth();
        $pageHeight = $page->getHeight();
        $image = $this->getImage();

        if (is_file($image)) {

            /* @var $image Zend_Pdf_Resource_Image */
            $image = Zend_Pdf_Image::imageWithPath($image);

            $imgWidth = $image->getPixelWidth();
            $imgHeight = $image->getPixelHeight();
            $percent = Mage::getStoreConfig('advancedinvoiceprinting_options/general/images_size');
            $imgWidth = $imgWidth * $percent / 100;
            $imgHeight = $imgHeight * $percent / 100;
            $x = ($pageWidth - $imgWidth) / 2;
            $y = ($pageHeight - $imgHeight) / 2;
            if (is_numeric(Mage::getStoreConfig('advancedinvoiceprinting_options/general/offset_x'))) {
                $x = $x + (int) Mage::getStoreConfig('advancedinvoiceprinting_options/general/offset_x');
            }
            if (is_numeric(Mage::getStoreConfig('advancedinvoiceprinting_options/general/offset_y'))) {
                $y = $y - (int) Mage::getStoreConfig('advancedinvoiceprinting_options/general/offset_y');
            }
            $page->drawImage($image, $x, $y, $x + $imgWidth, $y + $imgHeight);
        }
    }

    protected function getImage() {
        $WatermarkImage = Mage::getBaseDir('media') . '/sales/advancedinvoiceprinting/waterfallimage/' . Mage::getStoreConfig('advancedinvoiceprinting_options/general/images');
        $Opacity = Mage::getStoreConfig('advancedinvoiceprinting_options/general/opacity');
        $rotate = Mage::getStoreConfig('advancedinvoiceprinting_options/general/rotate_right');
        $resuleFile = substr($WatermarkImage, 0, strlen($WatermarkImage) - 4) . "_opacity" . $Opacity . "rotate_" . $rotate . ".jpg";
        if (!file_exists($resuleFile)) {
            $imgData = getimagesize($WatermarkImage);
            switch ($imgData['mime']) {
                case "image/jpeg": {
                        $overlay_src = imagecreatefromjpeg($WatermarkImage);
                        break;
                    }
                case "image/png": {
                        $overlay_src = imagecreatefrompng($WatermarkImage);
                        break;
                    }
            }

            $overlay_w = ImageSX($overlay_src);
            $overlay_h = ImageSY($overlay_src);
            // create true color canvas image:        
            $canvas_img = imagecreatetruecolor($overlay_w, $overlay_h);
            imagefill($canvas_img, 0, 0, imagecolorallocate($canvas_img, 255, 255, 255));
            // create true color overlay image:
            $overlay_img = imagecreatetruecolor($overlay_w, $overlay_h);
            imagealphablending($overlay_img, false);
            imagecopy($overlay_img, $overlay_src, 0, 0, 0, 0, $overlay_w, $overlay_h);
            imagesavealpha($overlay_img, true);

            imagedestroy($overlay_src);    // no longer needed
            $white = imagecolorallocate($overlay_img, 0xFF, 0xFF, 0xFF);
            imagecolortransparent($overlay_img, $white);

            // copy and merge the overlay image and the canvas image:
            imagecopymerge($canvas_img, $overlay_img, 0, 0, 0, 0, $overlay_w, $overlay_h, $Opacity);
            // output:
            $canvas_img = imagerotate($canvas_img, 360 - $rotate, imagecolorallocate($canvas_img, 255, 255, 255));
            imagejpeg($canvas_img, $resuleFile, 75);

            imagedestroy($overlay_img);
            imagedestroy($canvas_img);
        }
        return $resuleFile;
    }

    protected function _drawHeader(Zend_Pdf_Page $page) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::_drawHeader($page);
        }
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15, Zend_Pdf_Page::SHAPE_DRAW_STROKE);

        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));

        //columns headers
        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Products'),
            'feed' => 35
        );

        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('SKU'),
            'feed' => 290,
            'align' => 'right'
        );

        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Qty'),
            'feed' => 435,
            'align' => 'right'
        );

        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Price'),
            'feed' => 360,
            'align' => 'right'
        );

        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Tax'),
            'feed' => 495,
            'align' => 'right'
        );

        $lines[0][] = array(
            'text' => Mage::helper('sales')->__('Subtotal'),
            'feed' => 565,
            'align' => 'right'
        );

        $lineBlock = array(
            'lines' => $lines,
            'height' => 5
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array()) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::getPdf($invoices);
        }
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                    $page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
            );
            /* Add document text and number */
            $this->insertDocumentNumber(
                    $page, Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
            );
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            
            foreach ($invoice->getAllItems() as $item) {              
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }            
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }

    protected function _afterGetPdf() {
        parent::_afterGetPdf();
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable')) {
            foreach ($this->_pdf->pages as $page) {
                $this->drawFooter($page);
            }
            $this->drawPageNumber();
        }
    }

    protected function drawPageNumber() {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/pages/enable') == 0)
            return;
        $numPrefix = Mage::getStoreConfig('advancedinvoiceprinting_options/pages/format');
        $align = Mage::getStoreConfig('advancedinvoiceprinting_options/pages/alignment');
        $position = Mage::getStoreConfig('advancedinvoiceprinting_options/pages/position');
        $tmpPage = end($this->_pdf->pages);
        $textWitdh = $this->widthForStringUsingFontSize($numPrefix . " XX", $this->_setFontRegular($tmpPage, 10), 10);
        if ($position == MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Position::TOP) {
            $top = 820;
        } else {
            $top = 20;
        }


        $iCount = 1;
        foreach ($this->_pdf->pages as $page) {
            $textWitdh = $this->widthForStringUsingFontSize($numPrefix . " ".$iCount, $this->_setFontRegular($page, 10), 10);
            switch ($align) {
                case MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Alignment::LEFT: {
                        $left = 25;
                        break;
                    }
                case MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Alignment::CENTER: {
                        $left = $tmpPage->getWidth() / 2 - $textWitdh / 2;
                        break;
                    }
                case MW_AdvancedInvoicePrinting_Model_Admin_PageNumberConfig_Alignment::RIGHT: {
                        $left = $tmpPage->getWidth() - $textWitdh - 25;
                        break;
                    }
            }
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText($numPrefix . " " . $iCount, $left, $top, 'UTF-8');
            $iCount++;
        }
    }

    protected function drawFooter(&$page) {
        /* @var $page Zend_Pdf_Page */
        $textArr = explode("\n", Mage::getStoreConfig('advancedinvoiceprinting_options/general/footertext', Mage::app()->getStore()));
        $boxHeight = count($textArr) * 12;
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $top = $this->calFooterTopArea();
        $tmpTop = $top;
        $isDrawLine = false;
        $top -= 15;
        foreach ($textArr as $value) {
            if ($value !== '') {
                $page->drawText(trim(strip_tags($value)), 35, $top, 'UTF-8');
                $top -= self::FOOTER_TEXT_LINE_HEIGHT;
                $isDrawLine = true;
            }
        }
        if ($isDrawLine) {
            $page->drawLine(25, $tmpTop, 570, $tmpTop);
        }
    }
    
    protected function calFooterTopArea(){
        $textArr = explode("\n", Mage::getStoreConfig('advancedinvoiceprinting_options/general/footertext', Mage::app()->getStore()));
        $boxHeight = count($textArr) * self::FOOTER_TEXT_LINE_HEIGHT + 40;
        return $boxHeight;
    }
    
    /**
     * Insert order to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $putOrderId
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::insertOrder($page, $obj, $putOrderId);
        }
        $fillType = Zend_Pdf_Page::SHAPE_DRAW_STROKE;
        if ($obj instanceof Mage_Sales_Model_Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof Mage_Sales_Model_Order_Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 55, $fillType);
        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 55));
        $this->_setFontRegular($page, 10);

        if ($putOrderId) {
            $page->drawText(
                    Mage::helper('sales')->__('Order # ') . $order->getRealOrderId(), 35, ($top -= 30), 'UTF-8'
            );
        }
        $page->drawText(
                Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate(
                        $order->getCreatedAtStoreDate(), 'medium', false
                ), 35, ($top -= 15), 'UTF-8'
        );

        $top -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 275, ($top - 25), $fillType);
        $page->drawRectangle(275, $top, 570, ($top - 25), $fillType);

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));

        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true)
                ->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
            $shippingMethod = $order->getShippingDescription();
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
        $page->drawText(Mage::helper('sales')->__('Sold to:'), 35, ($top - 15), 'UTF-8');

        if (!$order->getIsVirtual()) {
            $page->drawText(Mage::helper('sales')->__('Ship to:'), 285, ($top - 15), 'UTF-8');
        } else {
            $page->drawText(Mage::helper('sales')->__('Payment Method:'), 285, ($top - 15), 'UTF-8');
        }

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight, $fillType);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        foreach ($billingAddress as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            foreach ($shippingAddress as $value) {
                if ($value !== '') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }

            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y - 25, $fillType);
            $page->drawRectangle(275, $this->y, 570, $this->y - 25, $fillType);

            $this->y -= 15;
            $this->_setFontBold($page, 12);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y, 'UTF-8');

            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments = $this->y - 15;
        } else {
            $yPayments = $addressesStartY;
            $paymentLeft = 285;
        }

        foreach ($payment as $value) {
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }

        if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->drawLine(25, ($top - 25), 25, $yPayments);
            $page->drawLine(570, ($top - 25), 570, $yPayments);
            $page->drawLine(25, $yPayments, 570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            $topMargin = 15;
            $methodStartY = $this->y;
            $this->y -= 15;

            foreach (Mage::helper('core/string')->str_split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y -= 15;
            }

            $yShipments = $this->y;
            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " "
                    . $order->formatPriceTxt($order->getShippingAmount()) . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 10;

            $tracks = array();
            if ($shipment) {
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10, $fillType);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page, 9);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Number'), 410, $yShipments - 7, 'UTF-8');

                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {

                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode != 'custom') {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    } else {
                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
                    }

                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 292, $yShipments, 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments, 'UTF-8');
                    $yShipments -= $topMargin - 5;
                }
            } else {
                $yShipments -= $topMargin - 5;
            }

            $currentY = min($yPayments, $yShipments);
            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $methodStartY, 25, $currentY); //left
            $page->drawLine(25, $currentY, 570, $currentY); //bottom
            $page->drawLine(570, $currentY, 570, $methodStartY); //right

            $this->y = $currentY;
            $this->y -= 15;
        }
    }

    public function insertDocumentNumber(Zend_Pdf_Page $page, $text) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::insertDocumentNumber($page, $text);
        }
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
        $this->_setFontRegular($page, 10);
        $docHeader = $this->getDocHeaderCoordinates();
        $page->drawText($text, 35, $docHeader[1] - 15, 'UTF-8');
    }

    protected function _calcAddressHeight($address) {
        if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::_calcAddressHeight($address);
        }
        $y = 0;
        foreach ($address as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 55, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $y += 15;
                }
            }
        }
        return $y;
    }
    
    public function drawLineBlocks(Zend_Pdf_Page $page, array $draw, array $pageSettings = array())
    {
         if (Mage::getStoreConfig('advancedinvoiceprinting_options/general/enable') == 0) {
            return parent::drawLineBlocks($page,$draw,$pageSettings);
        }
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                Mage::throwException(Mage::helper('sales')->__('Invalid draw line data. Please define "lines" array.'));
            }
            $lines  = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = array($column['text']);
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < $this->calFooterTopArea()) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font = Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    } else {
                        $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page, $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = array($column['text']);
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        if ($this->y - $lineSpacing < $this->calFooterTopArea()) {
                            $page = $this->newPage($pageSettings);
                        }

                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                }
                                else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                        }
                        $page->drawText($part, $feed, $this->y-$top, 'UTF-8');
                        $top += $lineSpacing;
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
        }
        return $page;
    }

}
