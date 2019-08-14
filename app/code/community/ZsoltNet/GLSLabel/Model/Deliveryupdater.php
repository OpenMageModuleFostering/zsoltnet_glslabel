<?php
require_once(dirname(__FILE__) . '/../controllers/HttpClient.class.php');

class ZsoltNet_GLSLabel_Model_Deliveryupdater
{
    private $client;

    private function init() {
        $hostname       = Mage::getStoreConfig('glslabelmodule/glslabel/hostname');
        $useragent      = Mage::getStoreConfig('glslabelmodule/glslabel/useragent');
        $this->client   = new HttpClient($hostname);
        $this->client->setUserAgent($useragent);
    }

    public function checkDeliveryStatus($labelid) {
        $client = $this->client;

        if (!$client->get('/tt_page.php', array('tt_value'=>$labelid))) {
            return false;
        } else {
            $content    = $client->getContent();
            $bodyStart  = strpos($content, "<div");
            $bodyEnd    = strpos($content, "<div/>");
            $body       = substr($content, $bodyStart, $bodyEnd-$bodyStart);
            if (strpos($body,"05-Kiszállítva")>0) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function cron() {
        $this->init();
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addAttributeToSelect('*')->addAttributeToFilter('status','processing_delivery')->load();
        foreach ($collection as $order) {
            foreach ($order->getShipmentsCollection()->getItems() as $shipment) {
                $labelid = $shipment->getData('increment_id');
                if ($this->checkDeliveryStatus($labelid)) {
                    if ($order->canComplete()) {
                        $order->complete()
                        ->save();
                    }
                } else {
                }
            }
            sleep(5);
        }
    }
}
