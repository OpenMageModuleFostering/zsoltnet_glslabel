<?php

class ZsoltNet_GLSLabel_Model_Mysql4_Order_Shipment extends Mage_Sales_Model_Mysql4_Order_Shipment
{

    /**
     * Perform actions before object save
     *
     * @param Varien_Object $object
     * @return Mage_Sales_Model_Mysql4_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $incrementId = Mage::getSingleton('admin/session')->getGLSNumber();
        if ($this->_useIncrementId && !$object->getIncrementId()) {
            $object->setIncrementId($incrementId);
        }
        parent::_beforeSave($object);
        return $this;
    }
}





