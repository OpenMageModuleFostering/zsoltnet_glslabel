<?php

class ZsoltNet_GLSLabel_Block_Widget_Grid_Column_Renderer_GLSStatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $order      = Mage::getModel('sales/order')->loadByIncrementId($row->getData('increment_id'));
        $isPersonal = ($order->getShippingCarrier()->getConfigData('title')==Mage::getStoreConfig('glslabelmodule/glslabel/personalservice', $order->getStoreId()));
        $isSpecial  = ($order->getShippingCarrier()->getConfigData('title')==Mage::getStoreConfig('glslabelmodule/glslabel/specialshipping', $order->getStoreId()));
        $id         = 0;
        $processing = false;
        $isCompleted= false;

        foreach ($order->getShipmentsCollection()->getItems() as $shipment) {
            $id = $shipment->getData('increment_id');
        }

        if (function_exists('isProcessingDelivery')) {
            //ZsoltNet CustomOrder modul
            $processing = $order->isProcessingDelivery();
        }

        $orderState = $order->getState();
        if ($orderState === Mage_Sales_Model_Order::STATE_COMPLETE) {
            $isCompleted = true;
        }

        if ($id && ($processing || $isCompleted)) {
            return "<span class='glslabel' rel=".Mage::helper("adminhtml")->getUrl("glslabel/shipment/getstatus/",array("labelid"=>$id)).">".parent::render($row)."</span>";
        } else if ($isPersonal){
            return Mage::getStoreConfig('glslabelmodule/glslabel/personalservicelabel', $order->getStoreId());
        } else if ($isSpecial){
            return Mage::getStoreConfig('glslabelmodule/glslabel/specialshippinglabel', $order->getStoreId());
        } else {
            return parent::render($row);
        }
    }
}