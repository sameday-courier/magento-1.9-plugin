<?php

/**
 * Class SamedayCourier_Shipping_Model_Carrier_Sameday
 */
class SamedayCourier_Shipping_Model_Carrier_Sameday extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'samedaycourier_shipping';

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if ($request->getDestCountryId() !== 'RO') {
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
                    $working_days = unserialize($service['working_days'],'');
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
        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;

        $rate = Mage::getModel('shipping/rate_result_method');

        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $shippingAddress = $quote->getShippingAddress();
        $subtotal = round($quote->getTotals()['subtotal']['value'], 2);

        $isEstimatedCostEnabled = $this->getConfigData('estimated_cost');

        $pickupPointId = Mage::getModel('samedaycourier_shipping/pickuppoint')->getDefaultPickupPoint();

        $estimateCostRequest = new Sameday\Requests\SamedayPostAwbEstimationRequest(
            $pickupPointId,
            null,
            new Sameday\Objects\Types\PackageType(
                0
            ),
            [new \Sameday\Objects\ParcelDimensionsObject($shippingAddress->getWeight())],
            $service['sameday_id'],
            new Sameday\Objects\Types\AwbPaymentType(
                1
            ),
            new Sameday\Objects\PostAwb\Request\AwbRecipientEntityObject(
                $shippingAddress->getCity(),
                $shippingAddress->getRegion(),
                ltrim(implode(" ", $shippingAddress->getStreet())),
                null,
                null,
                null,
                null
            ),
            0,
             $subtotal,
            null,
            array()
        );

        try {
            $estimation = $sameday->postAwbEstimation($estimateCostRequest);
            $estimatedCost = $estimation->getCost();
        } catch (\Sameday\Exceptions\SamedayBadRequestException $exception) {
            $estimatedCost = null;
        }


        if ($service['price_free'] !== '' && $subtotal >= $service['price_free']) {
            $service['price'] = 0;
        }

        if ($isEstimatedCostEnabled && $estimatedCost !== null) {
            $service['price'] = $estimatedCost;
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