<?php
class SamedayCourier_Shipping_Block_Adminhtml_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('id');
        $this->setId('samedaycourier_shipping_history_grid');
        $this->setDefaultDir('desc');
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(true);
    }

    /**
     * @return Varien_Data_Collection
     * @throws Exception
     */
    protected function _getCollectionClass()
    {
        Mage::helper('samedaycourier_shipping/api')->sameday;
        $order_id = $this->getRequest()->order_id;
        $packages = Mage::getModel('samedaycourier_shipping/package')->getPackagesForOrderId($order_id);

        $gridData = array();
        foreach ($packages as $package) {
            foreach ($package['history'] as $history) {
                $gridData[] = array(
                    'awb_parcel' => $package['awb_parcel'],
                    'status' => $history->getName(),
                    'status_label' => $history->getLabel(),
                    'state' => $history->getState(),
                    'date' => $history->getDate()->format('Y-m-d H:i:s'),
                    'county' => $history->getCounty(),
                    'transit_location' => $history->getTransitLocation(),
                    'reason' => $history->getReason()
                );
            }
        }

        $collection = new Varien_Data_Collection();

        foreach ($gridData as $item) {
            $rowObject = new Varien_Object();
            $collection->addItem($rowObject->setData($item));
        }

        return $collection;
    }

    protected function _prepareCollection()
    {
        $collection = $this->_getCollectionClass();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('parcel_number',
            array(
                'header'=> $this->__('Parcel Number'),
                'align' =>'left',
                'width' => '50px',
                'index' => 'awb_parcel',
                'filter' => false
            )
        );

        $this->addColumn('status',
            array(
                'header'=> $this->__('Status'),
                'index' => 'status',
                'filter' => false
            )
        );

        $this->addColumn('status_label',
            array(
                'header'=> $this->__('Label'),
                'index' => 'status_label',
                'filter' => false
            )
        );

        $this->addColumn('state',
            array(
                'header'=> $this->__('State'),
                'index' => 'state',
                'filter' => false
            )
        );

        $this->addColumn('date',
            array(
                'header'=> $this->__('Date'),
                'index' => 'date',
                'filter' => false
            )
        );

        $this->addColumn('county',
            array(
                'header'=> $this->__('County'),
                'index' => 'county',
                'filter' => false
            )
        );

        $this->addColumn('transit_location',
            array(
                'header'=> $this->__('Transit location'),
                'index' => 'transit_location',
                'filter' => false
            )
        );

        $this->addColumn('reason',
            array(
                'header'=> $this->__('Reason'),
                'index' => 'reason',
                'filter' => false
            )
        );

        return parent::_prepareColumns();
    }
}