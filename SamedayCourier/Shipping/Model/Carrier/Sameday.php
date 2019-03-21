<?php

class SamedayCourier_Shipping_Model_Carrier_Sameday extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'samedaycourier_shipping';

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if ($request->getData('dest_country_id') !== 'RO') {
            return null;
        }

        $result = Mage::getModel('shipping/rate_result');

        $availableServices = $this->getAvailableServices();

        if (!empty($availableServices)) {
            foreach ($availableServices as $service) {
                $result->append($this->_getRate($service));
            }
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return array(
            'samedaycourier_shipping'=> $this->getConfigData('title'),
        );
    }

    /**
     * @param bool $testing
     *
     * @return array
     */
    private function getAvailableServices()
    {
        $isTesting = $this->getConfigData('is_testing');

        $services = Mage::getModel('samedaycourier_shipping/service')
            ->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('is_testing', $isTesting)
            ->addFieldToFilter('status', array('gt' => 0))
            ->getData();

        $availableServices = array();
        foreach ($services as $service) {
            switch ($service['status']) {
                case 1:
                    $availableServices[] = $service;
                    break;

                case 2:
                    $working_days = unserialize($service['working_days']);
                    $day = strtolower(date('l', time()));

                    $todayFrom = $working_days["working_days_{$day}_from"];
                    $todayUntil = $working_days["working_days_{$day}_until"];

                    if ($todayFrom === '1' && $todayUntil === '1') {
                        // The service is inactive on this day
                        break;
                    }

                    $date_from = mktime($todayFrom[0], $todayFrom[1], $todayFrom[2], date('m', time()), date('d', time()), date('Y', time()));
                    $date_until = mktime($todayUntil[0], $todayUntil[1], $todayUntil[2], date('m', time()), date('d', time()), date('Y', time()));

                    $time = time();

                    if ($time < $date_from || $time > $date_until) {
                        // The service is out of interval
                        break;
                    }

                    $availableServices[] = $service;
                    break;
            }
        }

        return $availableServices;
    }

    private function _getRate($service)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $subtotal = round(Mage::getSingleton('checkout/session')->getQuote()->getTotals()['subtotal']->getData()['value'], 2);

        if ($service['price_free'] !== '' && $subtotal >= $service['price_free']) {
            $service['price'] = 0;
        }

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod($service['sameday_id']);
        $rate->setMethodTitle($service['name']);
        $rate->setPrice($service['price']);
        $rate->setCost($service['price']);

        return $rate;
    }
}