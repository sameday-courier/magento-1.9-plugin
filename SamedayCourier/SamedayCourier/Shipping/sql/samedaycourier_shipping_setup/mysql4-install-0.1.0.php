<?php

$installer = $this;

$installer->startSetup();

$awbTable = $installer->getConnection()
    ->newTable($installer->getTable('samedaycourier_shipping/awb'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Order Id')
    ->addColumn('awb_number', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Awb number')
    ->addColumn('parcels', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Parcels')
    ->addColumn('awb_cost', Varien_Db_Ddl_Table::TYPE_FLOAT, null, array(
        'nullable'  => true,
    ), 'Awb Cost');

$pickupPointTable = $installer->getConnection()
    ->newTable($installer->getTable('samedaycourier_shipping/pickuppoint'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('sameday_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Sameday Id')
    ->addColumn('sameday_alias', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Sameday Alias')
    ->addColumn('is_testing', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
    ), 'Is Testing')
    ->addColumn('is_default', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
    ), 'Is Default')
    ->addColumn('city', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
    ), 'City')
    ->addColumn('county', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
    ), 'County')
    ->addColumn('contactPersons', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
    ), 'Contact Persons');

$serviceTable = $installer->getConnection()
    ->newTable($installer->getTable('samedaycourier_shipping/service'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('sameday_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Sameday Id')
    ->addColumn('sameday_name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Sameday Name')
    ->addColumn('is_testing', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable'  => false,
    ), 'Is Testing')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Name')
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Price')
    ->addColumn('price_free', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Price Free')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Status')
    ->addColumn('working_days', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Working Days');

$packageTable = $installer->getConnection()
    ->newTable($installer->getTable('samedaycourier_shipping/package'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'ID')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'nullable'  => false,
    ), 'Order Id')
    ->addColumn('awb_parcel', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
    ), 'Awb Parcel')
    ->addIndex($installer->getIdxName('samedaycourier_shipping/package', array('order_id')),
        array('order_id'))
    ->addIndex($this->getIdxName('samedaycourier_shipping/package', array('awb_parcel')),
        array('awb_parcel'))
    ->addColumn('summary', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Summary')
    ->addColumn('history', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Summary')
    ->addColumn('expedition_status', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Expedition Status')
    ->addColumn('sync', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => true,
    ), 'Sync');

$lockerTable = $installer->getConnection()
    ->newTable($installer->getTable('samedaycourier_shipping/locker'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Id')
    ->addColumn('locker_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Locker Id')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'Name')
    ->addColumn('city', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'City')
    ->addColumn('county', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'County')
    ->addColumn('address', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'Address')
    ->addColumn('lat', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'Lat')
    ->addColumn('lng', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
    ), 'Lng')
    ->addColumn('postal_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'Postal code')
    ->addColumn('boxes', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'nullable'  => false,
        'lenght' => 255,
    ), 'Boxes')
    ->addColumn('is_testing', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => true,
    ), 'Is testing');

$installer->getConnection()->createTable($awbTable);
$installer->getConnection()->createTable($pickupPointTable);
$installer->getConnection()->createTable($serviceTable);
$installer->getConnection()->createTable($packageTable);
$installer->getConnection()->createTable($lockerTable);

$installer->endSetup();