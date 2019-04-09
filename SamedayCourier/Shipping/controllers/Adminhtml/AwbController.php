<?php

class SamedayCourier_Shipping_Adminhtml_AwbController extends Mage_Adminhtml_Controller_Action
{
    private $store;
    private $awbModel;
    private $packageModel;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->store = Mage::app()->getStore();
        $this->awbModel = Mage::getModel('samedaycourier_shipping/awb');
        $this->packageModel = Mage::getModel('samedaycourier_shipping/package');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('samedaycourier_shipping/awb');
    }

    /**
     * Generate Awb
     */
    public function indexAction()
    {
        $awbIndexBlock = $this->getLayout()->createBlock(
            'samedaycourier_shipping/adminhtml_awb'
        );

        $this->loadLayout();
        $this->_addContent($awbIndexBlock);
        $this->_title($this->__('Generate Awb'));

        $this->renderLayout();
    }

    public function generateAwbAction()
    {
        $formData = $this->getRequest()->getParams();
        $order = Mage::getModel('sales/order')->load($formData['order_id'])->getData();
        $shippingDetails = Mage::getModel('sales/order_address')->load($order['shipping_address_id'])->getData();

        $orderInfo = array_merge($shippingDetails, $formData, $order);

        try {
            // No errors, post AWB.
            $awb = $this->postAwb($orderInfo);
        } catch (Exception $e) {
            $errors = $e->getErrors();
        }

        if (isset($awb)) {
            $this->awbModel->saveAwb(array(
                'order_id' => $orderInfo['order_id'],
                'awb_number' => $awb->getAwbNumber(),
                'parcels' => serialize($awb->getParcels()),
                'awb_cost' =>  $awb->getCost()
            ));

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('samedaycourier_shipping/data')->__('Awb generated successfully !')
            );
        } elseif (isset($errors)) {
            $all_errors = $this->parseErrors($errors);
            Mage::getSingleton('adminhtml/session')->addError($all_errors);
            return $this->_redirect('adminhtml/awb/index/order_id/' . $orderInfo['order_id']);
        }

        return $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderInfo['order_id']);
    }

    /**
     * @param $errors
     * @return string
     */
    private function parseErrors($errors)
    {
        $all_errors = '';
        foreach ($errors as $error) {
            foreach ($error['errors'] as $message) {
                $all_errors .= implode('.', $error['key']) . ':' . $message . '<br/>';
            }
        }

        return $all_errors;
    }

    /**
     * index Layout for form in order to add new parcel to AWB
     */
    public function addParcelToAwbAction()
    {
        $parcelBlock = $this->getLayout()->createBlock(
            'samedaycourier_shipping/adminhtml_parcel'
        );

        $this->loadLayout();
        $this->_addContent($parcelBlock);
        $this->_title($this->__('Add Parcel'));

        $this->renderLayout();
    }

    public function addParcelAction()
    {
        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;
        $params = $this->getRequest()->getParams();
        $order_id = $params['order_id'];
        $awb_number = $this->awbModel->getAwbForOrderId($order_id)['awb_number'];

        if (!$awb_number) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('samedaycourier_shipping/data')->__('Awb not found')
            );
        } else {
            $position = $this->awbModel->getPosition($order_id);
            $request = new \Sameday\Requests\SamedayPostParcelRequest(
                $awb_number,
                new Sameday\Objects\ParcelDimensionsObject(
                    round($params['package_weight'], 2),
                    round($params['package_width'], 2),
                    round($params['package_length'],2),
                    round($params['package_height'], 2)
                ),
                $position,
                $params['observation'],
                null,
                $params['is_last']
            );

            try {
                $parcels = $sameday->postParcel($request);
            } catch (\Sameday\Exceptions\SamedayBadRequestException $e) {
                $errors = $e->getErrors();
            }

            if (isset($errors)){
                $all_errors = $this->parseErrors($errors);
                Mage::getSingleton('adminhtml/session')->addError($all_errors);
            } else {

                $response = array(new \Sameday\Objects\PostAwb\ParcelObject(
                    $position,
                    $parcels->getParcelAwbNumber()
                )
                );

                $this->awbModel->updateParcels($order_id, $response);

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('samedaycourier_shipping/data')->__('The package has been successfully added !')
                );
            }
        }

        return $this->_redirect('adminhtml/awb/addParcelToAwb/order_id/' . $order_id);
    }

    public function showAsPdfAction()
    {
        $order_id = $this->getRequest()->order_id;
        $awb = $this->awbModel->getAwbForOrderId($order_id);

        if (!$awb) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('samedaycourier_shipping/data')->__('Awb not found !')
            );

            return $this->_redirect('adminhtml/sales_order/view/order_id/' . $order_id);
        }

        $this->getResponse()->setHeader('Content-type', 'application/pdf; charset=UTF-8');

        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;

        $content = $sameday->getAwbPdf(new \Sameday\Requests\SamedayGetAwbPdfRequest($awb['awb_number'],
            new \Sameday\Objects\Types\AwbPdfType(\Sameday\Objects\Types\AwbPdfType::A4)));

        $this->getResponse()->setBody($content->getPdf());
    }

    public function showHistoryAction()
    {
        $this->loadLayout();

        $awbSummaryBlock = $this->getLayout()
            ->createBlock('samedaycourier_shipping/adminhtml_summary');
        $this->_addContent($awbSummaryBlock);
        $awbHistoryBlock = $this->getLayout()
            ->createBlock('samedaycourier_shipping/adminhtml_history');
        $this->_addContent($awbHistoryBlock);

        $this->_title($this->__('Show History'));

        $this->renderLayout();
    }

    public function refreshHistoryAction()
    {
        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;
        $order_id = $this->getRequest()->order_id;

        $awb = $this->awbModel->getAwbForOrderId($order_id);

        if (!$awb) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('samedaycourier_shipping/data')->__('Awb not found')
            );

            return $this->_redirect('adminhtml/awb/showHistory/order_id/' . $order_id);
        }

        $parcels = unserialize($awb['parcels']);
        foreach ($parcels as $parcel) {
            $parcelStatus = $sameday->getParcelStatusHistory(new \Sameday\Requests\SamedayGetParcelStatusHistoryRequest($parcel->getAwbNumber()));
            $this->packageModel->refreshHistory(
                $awb['order_id'],
                $parcel->getAwbNumber(),
                serialize($parcelStatus->getSummary()),
                serialize($parcelStatus->getHistory()),
                serialize($parcelStatus->getExpeditionStatus())
            );
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('samedaycourier_shipping/data')->__('Status Summary updated successfully !')
        );

        return $this->_redirect('adminhtml/awb/showHistory/order_id/' . $order_id);
    }

    public function deleteAwbAction()
    {
        $order_id = $this->getRequest()->order_id;

        $awb = $this->awbModel->getAwbForOrderId($order_id);
        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;

        if ($awb) {
            try {
                $sameday->deleteAwb(new Sameday\Requests\SamedayDeleteAwbRequest($awb['awb_number']));
                $this->awbModel->deleteAwb($awb['id']);
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('samedaycourier_shipping/data')->__('Awb removed successfully !')
                );
            } catch (\Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('samedaycourier_shipping/data')->__($e->getMessage())
                );
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('samedaycourier_shipping/data')->__('Awb not found !')
            );
        }

        return $this->_redirect('adminhtml/sales_order/view/order_id/' . $order_id);
    }

    /**
     * @param $orderInfo
     * @return \Sameday\Responses\SamedayPostAwbResponse
     * @throws \Sameday\Exceptions\SamedayAuthenticationException
     * @throws \Sameday\Exceptions\SamedayAuthorizationException
     * @throws \Sameday\Exceptions\SamedayBadRequestException
     * @throws \Sameday\Exceptions\SamedayNotFoundException
     * @throws \Sameday\Exceptions\SamedayOtherException
     * @throws \Sameday\Exceptions\SamedaySDKException
     * @throws \Sameday\Exceptions\SamedayServerException
     */
    private function postAwb($params)
    {
        $sameday = Mage::helper('samedaycourier_shipping/api')->sameday;

        $params['service_id'] = explode('_', $params['shipping_method'], '3');
        $params['service_id'] = $params['service_id'][2];


        $parcelDimensions[] = new \Sameday\Objects\ParcelDimensionsObject(
            $params['package_weight'],
            $params['package_length'],
            $params['package_height'],
            $params['package_width']
        );

        $companyObject = null;
        if (strlen($params['company'])) {
            $companyObject = new \Sameday\Objects\PostAwb\Request\CompanyEntityObject(
                $params['company'],
                isset($params['vat_id']) ? $params['vat_id'] : '',
                '',
                '',
                ''
            );
        }

        $request = new \Sameday\Requests\SamedayPostAwbRequest(
            $params['pickup_point'],
            null,
            new \Sameday\Objects\Types\PackageType($params['package_type']),
            $parcelDimensions,
            $params['service_id'],
            new \Sameday\Objects\Types\AwbPaymentType($params['awb_payment']),
            new \Sameday\Objects\PostAwb\Request\AwbRecipientEntityObject(
                $params['city'],
                $params['region'],
                trim($params['street']),
                $params['customer_firstname'] . ' ' . $params['customer_lastname'],
                $params['telephone'],
                $params['email'],
                $companyObject
            ),
            $params['insured_value'],
            $params['ramburs'],
            new \Sameday\Objects\Types\CodCollectorType(\Sameday\Objects\Types\CodCollectorType::CLIENT),
            null,
            array(),
            null,
            null,
            $params['observation']
        );

        return $sameday->postAwb($request);
    }
}