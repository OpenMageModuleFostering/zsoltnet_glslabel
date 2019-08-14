<?php

/**
 * Event observer model
 *
 *
 */
class ZsoltNet_GLSLabel_Model_Observer
{
    /**
     * Adds virtual grid column to order grid records generation
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addColumnToResource(Varien_Event_Observer $observer)
    {
        /* @var $resource Mage_Sales_Model_Mysql4_Order */
        $resource = $observer->getEvent()->getResource();
        $resource->addVirtualGridColumn(
            'gls_id',
            'sales/shipment',
            array('entity_id' => 'order_id'),
            'increment_id'
        );
    }

    public function addMassActionAddGLSButton(Varien_Event_Observer $observer)
    {
        $block  = $observer->getEvent()->getBlock();

        if(($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction)
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('pdfshipments_order', array(
                'label' => Mage::helper('sales')->__('Print Packingslips'),
                'url'   => Mage::app()->getStore()->getUrl('glslabel/shipment/massprint'),
            ));
        }

        if(($block instanceof Mage_Adminhtml_Block_Sales_Order_View)
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $request    = $block->getRequest();
            $params     = $request->getParams();
            $orderId    = $params['order_id'];
            $order      = Mage::getModel('sales/order')->load($orderId);

            if ($order->canShip() && !$order->getForcedDoShipmentWithInvoice()) {
                    $block->addButton('order_gls', array(
                    'label'     => Mage::helper('sales')->__('GLS'),
                    'onclick'   => 'window.open(\'' . Mage::helper("adminhtml")->getUrl("glslabel/shipment/index/",array("orderid"=>$orderId)) . '\')',
                    'class'     => 'go'
                ), 0, 60);
            }
        }
    }

}

