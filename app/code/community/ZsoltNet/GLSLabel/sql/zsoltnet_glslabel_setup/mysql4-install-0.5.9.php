<?php
/**
 * Setup scripts, add new column and fulfills
 * its values to existing rows
 *
 */

/* @var $this Mage_Sales_Model_Mysql4_Setup */
$this->startSetup();

// Add column to grid table
$this->getConnection()->addColumn(
    $this->getTable('sales/order_grid'),
    'gls_id',
    "varchar(20) not null default ''"
);

// Add key to table for this field,
// it will improve the speed of searching & sorting by the field
$this->getConnection()->addKey(
    $this->getTable('sales/order_grid'),
    'gls_id',
    'gls_id'
);

// Now you need to fullfill existing rows with data from address table
$select = $this->getConnection()->select();
$select->join(
    array('shipment'=>$this->getTable('sales/shipment')),
    $this->getConnection()->quoteInto('shipment.order_id = order_grid.entity_id'),
    array('gls_id' => 'increment_id')
);

$this->getConnection()->query(
    $select->crossUpdateFromSelect(
        array('order_grid' => $this->getTable('sales/order_grid'))
    )
);

$this->endSetup();

