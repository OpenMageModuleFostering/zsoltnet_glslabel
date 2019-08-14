<?php
require_once('HttpClient.class.php');
require_once('pdf/rotation.php');
require_once('pdf/concat_pdf.php');

class ZsoltNet_GLSLabel_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    private $hostname;
    private $client;
    private $debug          = false;
    private $debugclient    = false;
    private $debugdir;
    private $cookielength   = 26;

    private function errorHandle($message, $error = NULL, $errorfile ) {
        $this->_getSession()->addError($this->__($message));
        if ($error!=NULL) {
            $h = fopen($this->debugdir."/glslabel_error".$errorfile,"w");
            fwrite($h,$error);
            fclose($h);
        }
    }

    /**
     * Initialize shipment items QTY
     */
    protected function _getItemQtys()
    {
        $data = $this->getRequest()->getParam('shipment');
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }
        return $qtys;
    }

    /**
     * Initialize shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    protected function _initShipment($orderId)
    {
        $shipment   = false;
        $order      = Mage::getModel('sales/order')->load($orderId);

            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->_getSession()->addError($this->__('The order no longer exists.'));
                return false;
            }
            /**
             * Check shipment is available to create separate from invoice
             */
            if ($order->getForcedDoShipmentWithInvoice()) {
                $this->_getSession()->addError($this->__('Cannot do shipment for the order separately from invoice.'));
                return false;
            }
            /**
             * Check shipment create availability
             */
            if (!$order->canShip()) {
                $this->_getSession()->addError($this->__('Cannot do shipment for the order.'));
                return false;
            }
            $savedQtys = $this->_getItemQtys();
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);

            $tracks = $this->getRequest()->getPost('tracking');
            if ($tracks) {
                foreach ($tracks as $data) {
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->addData($data);
                    $shipment->addTrack($track);
                }
            }

        return $shipment;
    }

    /**
     * Decides if we need to create dummy shipment item or not
     * for eaxample we don't need create dummy parent if all
     * children are not in process
     *
     * @deprecated after 1.4, Mage_Sales_Model_Service_Order used
     * @param Mage_Sales_Model_Order_Item $item
     * @param array $qtys
     * @return bool
     */
    protected function _needToAddDummy($item, $qtys) {
        if ($item->getHasChildren()) {
            foreach ($item->getChildrenItems() as $child) {
                if ($child->getIsVirtual()) {
                    continue;
                }
                if ((isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) || (!isset($qtys[$child->getId()]) && $child->getQtyToShip())) {
                    return true;
                }
            }
            return false;
        } else if($item->getParentItem()) {
            if ($item->getIsVirtual()) {
                return false;
            }
            if ((isset($qtys[$item->getParentItem()->getId()]) && $qtys[$item->getParentItem()->getId()] > 0)
                || (!isset($qtys[$item->getParentItem()->getId()]) && $item->getParentItem()->getQtyToShip())) {
                return true;
            }
            return false;
        }
    }

    /**
     * Save shipment and order in one transaction
     * @param Mage_Sales_Model_Order_Shipment $shipment
     */
    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    /**
     * Common method
     */
    protected function _initAction() {
        $this->hostname = Mage::getStoreConfig('glslabelmodule/glslabel/hostname');
        $this->debugdir = Mage::getStoreConfig('checkout/ledgerinvoice/debugdir');
        if (!is_dir($this->debugdir)) mkdir($this->debugdir);
        return $this;
    }

    private function login() {
        $useragent      = Mage::getStoreConfig('glslabelmodule/glslabel/useragent');
        $this->client   = new HttpClient($this->hostname);
        $client         = $this->client;

        $client->setUserAgent($useragent);
        if ($this->debugclient) {
            $client->setDebug(true);
        }

        $cookie         = Mage::getSingleton('admin/session')->getGLSCookie(); 
        $cookiets       = Mage::getSingleton('admin/session')->getGLSCookieTs(); 
        $timeout        = Mage::getStoreConfig('glslabelmodule/glslabel/timeout');

        if ($this->debug) {
            $h = fopen($this->debugdir."/glsdebug1.cookie1.txt","w");
            fwrite($h,$cookie);
            fclose($h);
        }

        if (isset($cookie)) {
            //we have a working GLS-cookie, checking the timestamp
            if ($cookiets + $timeout*60 > time()) {
                $client->setCookies(array('PHPSESSID'=>"".$cookie));
                return true;
            }
        }

        $username       = Mage::getStoreConfig('glslabelmodule/glslabel/username');
        $password       = Mage::getStoreConfig('glslabelmodule/glslabel/password');

        if (!$client->get('/index.php' )) {
                $this->errorHandle('Hiba történt az index.php betöltése közben!', $client->getError(), "");
                return false;
        } else {
            $content    = $client->getContent();
        }
        if ($this->debug) {
            $h = fopen($this->debugdir."/glsdebug2.index.txt","w");
            fwrite($h,$content);
            fclose($h);
        }

        $cookies= $client->getHeader('set-cookie');
        $cookie = substr($cookies, strpos($cookies,"=")+1, $this->cookielength);
        $client->setCookies(array('PHPSESSID'=>"".$cookie));
        if ($this->debug) {
            $h = fopen($this->debugdir."/glsdebug3.cookie2.txt","w");
            fwrite($h,$cookie);
            fclose($h);
        }

        //storing cookie info in the session
        Mage::getSingleton('admin/session')->setGLSCookie($cookie);
        Mage::getSingleton('admin/session')->setGLSCookieTs(time());

        if (!$client->post('/login.php', array(
            'page' => '',
            'username' => $username,
            'password' => $password))) {
                $this->errorHandle('Hiba történt belépés közben!', $client->getError(), "");
                return false;
        } else {
            $content    = $client->getContent();
        }
        if ($this->debug) {
            $h = fopen($this->debugdir."/glsdebug4.login.txt","w");
            fwrite($h,$content);
            fclose($h);
        }
        if (substr_count($content,"Bejelentkezés")>0) { 
            $this->errorHandle('Hiba történt belépés közben! Valószínűleg megváltozott a bejelentkezőoldal.');
            return false;
        }

        if (!$client->post('/welcome.php')) {
            $this->errorHandle('Hiba történt a welcome page-en!', $client->getError(), "");
            return false;
        } else {
            $content    = $client->getContent();
        }

        if ($this->debug) {
            $h = fopen($this->debugdir."/glsdebug5.welcome.txt","w");
            fwrite($h,$content);
            fclose($h);
        }

        return true;
    }

    private function generateLabel($content, $orderId = NULL, $code = NULL) {
        $dir        = Mage::getStoreConfig('glslabelmodule/glslabel/tmpdir');
        if (!is_dir($dir)) mkdir($dir);
        $tempfile   = $dir."/".time().".pdf";
        $file       = fopen($tempfile,"w");
        fwrite($file,$content);
        fclose($file);

        $long       = Mage::getStoreConfig('glslabelmodule/glslabel/long_size');
        $pdf        = new FPDIr();
        $pagecount  = $pdf->setSourceFile($tempfile);
        $tplidx     = $pdf->importPage(1, '/MediaBox');
        $center     = $long/2;

        $pdf->addPage();
        $pdf->Rotate(90,$center,0);
        $pdf->useTemplate($tplidx, -$center, 0, $long);

        if ($orderId) {
            Mage::getSingleton('admin/session')->setGLSNumber($code);
            $this->saveNewShipment($orderId, $code);
        }

        unlink($tempfile);
        return $pdf;
    }

    private function getLabel($orderId) {
        $client     = $this->client;

        $order      = Mage::getModel('sales/order')->load($orderId);
        if (!$order->canShip()) {
            return NULL;
        }

        //getting the parameters
        $sId        = Mage::getStoreConfig('glslabelmodule/glslabel/sender_id', $order->getStoreId());
        $sName      = Mage::getStoreConfig('glslabelmodule/glslabel/sender_name', $order->getStoreId());
        $sAddress   = Mage::getStoreConfig('glslabelmodule/glslabel/sender_address', $order->getStoreId());
        $sCity      = Mage::getStoreConfig('glslabelmodule/glslabel/sender_city', $order->getStoreId());
        $sZip       = Mage::getStoreConfig('glslabelmodule/glslabel/sender_zipcode', $order->getStoreId());
        $sCountry   = Mage::getStoreConfig('glslabelmodule/glslabel/sender_country', $order->getStoreId());
        $sContact   = Mage::getStoreConfig('glslabelmodule/glslabel/sender_contact', $order->getStoreId());
        $sPhone     = Mage::getStoreConfig('glslabelmodule/glslabel/sender_phone', $order->getStoreId());

        $cName      = $order->getShippingAddress()->getName();
        $cCompany   = $order->getShippingAddress()->getCompany();
        $cAddress   = $order->getShippingAddress()->getStreet(1);
        $cCity      = $order->getShippingAddress()->getCity();
        $cZip       = $order->getShippingAddress()->getPostcode();
        $cCountry   = $order->getShippingAddress()->getCountryId();
        $cContact   = $order->getShippingAddress()->getName();
        $cPhone     = $order->getShippingAddress()->getTelephone();

        if (trim($cCompany)!="") {
            $cName = $cCompany." (".$cName.")";
        }

        $date       = Mage::getModel('core/date')->date("Y.m.d ");

        //shipment details
        $_totalData = $order->getData();
        $total      = round($_totalData['grand_total']);
        if ($_totalData['cod_fee']=="") {
            $total  = "";
        }
        $qty        = 0;
        $types      = array();
        foreach ($order->getAllItems() as $item) {
            $qty += $item->getQtyToShip();
            $data   = $item->getData();
            $product= Mage::getModel('catalog/product')->load($data['product_id']);
            $data   = $product->getData();
            $attset = Mage::getModel('eav/entity_attribute_set')->load($data['attribute_set_id'], 'attribute_set_id')->getAttributeSetName();
            if (array_search($attset, $types) === FALSE){
                array_push($types, $attset);
            }
        }

        $cont = "";
        foreach ($types as $type) {
            $cont .= $type. ",";
        }
        $cont = substr($cont,0,strlen($cont)-1) . "(#".$order->getRealOrderId().")";

        if (!$client->post('/createlabel.php', array(
            'posted'=>'1',
            'submit_id'=>'print',
            'parcelnrs'=>'',
            'inputid'=>'',
            'param1'=>'',
            'retval'=>'',
            'selectbymatchcode'=>'',
            'getaddr'=>'',
            'printit'=>'',
            'showlabels'=>'',
            'showlabels_list'=>'',
            'resetid'=>'',
            'exchangeservice'=>'',
            'EXCH'=>'',
            'getcityname'=>'0',
            'senderid'=>$sId,
            'sender_name'=>$sName,
            'sender_address'=>$sAddress,
            'sender_city'=>$sCity,
            'sender_zipcode'=>$sZip,
            'sender_country'=>$sCountry,
            'sender_contact'=>$sContact,
            'sender_phone'=>$sPhone,
            'consig_matchcode'=>'',
            'consig_id'=>'',
            'consig_name'=>$cName,
            'consig_address'=>$cAddress,
            'consig_city'=>$cCity,
            'consig_zipcode'=>$cZip,
            'consig_country'=>$cCountry,
            'consig_contact'=>$cContact,
            'consig_phone'=>$cPhone,
            'consig_email'=>'',
            'pclcount'=>'1',
            'pickupdate'=>$date,
            'content'=>$cont,
            'clientref'=>'',
            'codamount'=>$total,
            'codref'=>$order->getRealOrderId(),
            'servparam_02'=>'',
            'servparam_03'=>'',
            'servparam_04'=>'',
            'servparam_05'=>'',
            'servparam_08'=>'',
            'servparam_09'=>'',
            'servparam_11'=>'',
            'servparam_12'=>'',
            'servparam_13'=>'',
            'servparam_14'=>'',
            'servparam_15'=>'',
            'servparam_16'=>'',
            'servparam_18'=>'Tisztelt címzett! Csomagját a köv. munkanap kézbesítjük. A csomag száma #ParcelNr#, az utánvét összege: #COD#.',
            'servparam_19'=>''))) {
                $this->errorHandle('Hiba történt a paraméterek elküldése közben ('.$order->getRealOrderId().')!');
                return NULL;
        } else {
            $content    = $client->getContent();
            if ($this->debug) {
                $h      = fopen($this->debugdir."/glsdebug6.createlabel.txt","w");
                fwrite($h,$content);
                fclose($h);
            }

            $pattern    = "/.*self.document.mainform.printit.value='(\d*)/";
            preg_match($pattern, $content, $matches);
            $code = $matches[1];

            if ($this->debug) {
                $h      = fopen($this->debugdir."/glsdebug7.code.txt","w");
                fwrite($h,$code);
                fclose($h);
            }

            if (strlen($code)>12 || strlen($code)<8) {
                $this->errorHandle('Hiba történt a címke készítése közben ('.$order->getRealOrderId().')!', $content, "");
                return NULL;
            }

            $client->get('/printlabel_l.php', array('pcls'=>$code));//vmiert 2x kell lekerni a PDF-et...
            if (!$client->get('/printlabel_l.php', array('pcls'=>$code))) {
                $this->errorHandle('Hiba történt a címke letöltése közben ('.$order->getRealOrderId().')!', $content, "");
                return NULL;
            } else {
                return $this->generateLabel($client->getContent(), $orderId, $code);
            }

        }

    }

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     */
    public function saveNewShipment($orderId, $number)
    {
        $title = Mage::getStoreConfig('glslabelmodule/glslabel/trackingtitle');

        try {
            $carrier = "custom";
            if (empty($number)) {
                Mage::throwException($this->__('Tracking number can not be empty.'));
            }
            if ($shipment = $this->_initShipment($orderId)) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($number)
                    ->setCarrierCode($carrier)
                    ->setTitle($title);
//                $shipment->addTrack($track);
                $shipment->register();
                $this->_saveShipment($shipment);
                return;
            } else {
                $this->_forward('noRoute');
                return;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Cannot save shipment.'));
        }
        $this->_redirect('*/*/new', array('order_id' => $orderId));
    }

    private function getLabelById($labelId) {
        $client     = $this->client;

        if (!$client->get('/printlabel_l.php', array('pcls'=>$labelId))) {
            $this->errorHandle('Hiba történt a címke letöltése közben ('.$labelId.')!');
            return NULL;
        } else {
            return $this->generateLabel($client->getContent());
        }
    }

    private function singleOrder($orderId) {
        $this->_initAction();
        if (!$this->login()) {
            return NULL;
        }
        $pdf = $this->getLabel($orderId);
        return $pdf;
    }

    public function indexAction() {
        $orderId    = $this->getRequest()->orderid;
        $pdf        = $this->singleOrder($orderId);
        if ($pdf==NULL) {
            $this->_getSession()->addError($this->__('Nem lehet címkét készíteni a rendeléshez.'));
            return;
        }
        $this->_getSession()->addSuccess($this->__('A címke elkészült'));
        $pdf->Output('glslabel.pdf', 'D');
    }

    public function massprintAction() {
        $orderIds   = $this->getRequest()->getPost('order_ids', array());
        $dir        = Mage::getStoreConfig('glslabelmodule/glslabel/tmpdir');
        if (!is_dir($dir)) mkdir($dir);
        $labels     = new concat_pdf();
        $error      = 0;
        $number     = 0;
        foreach (array_reverse($orderIds, true) as $orderId) {
            $label  = $this->singleOrder($orderId);
            if ($label==NULL) {
                $error = 1;
                continue;
            }
            $number++;
            $label->Output($dir.'/label.'.$number.'.pdf', 'F');
            $labels->addFile($dir.'/label.'.$number.'.pdf'); 
        }
        if ($error) {
            $this->_getSession()->addError($this->__('Néhány szállítási címke nem sikerült.'));
        } else {
            $this->_getSession()->addSuccess($this->__('A címkék sikeresen elkészültek.'));
        }
        $labels->concat();
        //torles
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") unlink($dir."/".$object);
        }
        $labels->Output('labels.pdf', 'D');
    }

    public function printlabelAction() {
        $this->_initAction();
        $labelId = $this->getRequest()->labelid;

        if (!$this->login()) {
            return;
        }
        $pdf = $this->getLabelById($labelId);
        if ($pdf==NULL) {
            return;
        }
        $pdf->Output('glslabel.pdf', 'D');
    }

    public function getstatusAction() {
        $this->_initAction();
        $useragent      = Mage::getStoreConfig('glslabelmodule/glslabel/useragent');
        $client         = new HttpClient($this->hostname);
        $client->setUserAgent($useragent);
        $labelId        = $this->getRequest()->labelid;

        if (!$client->get('/tt_page.php', array('tt_value'=>$labelId))) {
            return "error";
        } else {
            $content    = $client->getContent();
            $bodyStart  = strpos($content, "<div");
            $bodyEnd    = strpos($content, "<div/>");
            $body       = substr($content, $bodyStart, $bodyEnd-$bodyStart);
            $body       = str_replace("margin-top: 50px", "margin-top: 0px", $body);
            echo $body;
        }
    }

}
