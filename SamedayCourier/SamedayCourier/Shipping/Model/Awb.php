<?php

/**
 * Class SamedayCourier_Shipping_Model_Awb
 */
class SamedayCourier_Shipping_Model_Awb extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('samedaycourier_shipping/awb');
    }

    public function getAwbForOrderId($order_id)
    {
        $awb = Mage::getModel('samedaycourier_shipping/awb')
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('order_id', $order_id)
            ->getData();

        if (!empty($awb)) {
            return $awb[0];
        }
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function saveAwb($data)
    {
        $awb = Mage::getModel('samedaycourier_shipping/awb');
        $this->setParams($awb, $data);
        try {
            $awb->save();
        } catch (Exception $e) {
        }
    }

    public function deleteAwb($id)
    {
        $awb = Mage::getModel('samedaycourier_shipping/awb')->load($id);
        $awb->delete();
    }

    public function updateParcels($order_id, $parcel)
    {
        $awb = $this->getAwbForOrderId($order_id);

        if (!$awb) {
            return;
        }

        $awb_id = $awb['id'];
        $parcels = unserialize($awb['parcels']);
        $parcels = array_merge($parcels, $parcel);

        $awb = Mage::getModel('samedaycourier_shipping/awb')->load($awb_id);
        $awb->setParcels(serialize($parcels));

        $awb->save();
    }

    /**
     * @param $order_id
     * @return int
     */
    public function getPosition($order_id)
    {
        $awb = $this->getAwbForOrderId($order_id);
        $parcels = unserialize($awb['parcels']);
        $nrOfParcels = count($parcels);

        return $nrOfParcels + 1;
    }

    /**
     * @param $awb
     * @param $data
     */
    private function setParams($awb, $data)
    {
        $awb->setOrderId($data['order_id']);
        $awb->setAwbNumber($data['awb_number']);
        $awb->setParcels($data['parcels']);
        $awb->setAwbCost($data['awb_cost']);
    }
}