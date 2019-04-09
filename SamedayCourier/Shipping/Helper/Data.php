<?php

/**
 * Class SamedayCourier_Shipping_Helper_Data
 */
class SamedayCourier_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->_getRequest()->order_id;
    }

    /**
     * @return array|null
     */
    public function getShippingMethodSameday()
    {
        $data = array();
        $order_id = $this->getOrderId();
        $shipping_method = Mage::getModel('sales/order')->load($order_id)->getData()['shipping_method'];
        $shipping_method = explode("_", $shipping_method);

        if ($shipping_method[0] !== 'sameday') {
            return null;
        }

        $awb = Mage::getModel('samedaycourier_shipping/awb')->getAwbForOrderId($order_id);

        if (!is_null($awb)) {
            $data['awb_number'] = $awb['awb_number'];
        }

        return $data;
    }
}