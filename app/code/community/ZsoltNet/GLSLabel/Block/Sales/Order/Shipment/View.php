<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order view
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ZsoltNet_GLSLabel_Block_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
{

    public function __construct()
    {
        parent::__construct();

        $order = $this->getOrder();
        $this->_removeButton('print');

        if ($this->getShipment()->getId()) {
            $this->_addButton('print', array(
                'label'     => Mage::helper('sales')->__('Print'),
                'class'     => 'save',
                'onclick'   => 'window.open(\''.$this->getPrintUrl().'\')'
                )
            );
        }
    }

    public function getPrintUrl()
    {
        if ($this->getShipment()->getOrder()->getShippingCarrier()->getConfigData('title')==Mage::getStoreConfig('glslabelmodule/glslabel/glsservice')) {
            return Mage::helper("adminhtml")->getUrl("glslabel/shipment/printlabel/",array("labelid"=>$this->getShipment()->getData('increment_id')));
        } else {
            return $this->getUrl('*/*/print', array(
                'invoice_id' => $this->getShipment()->getId()
            ));
        }
    }
}
