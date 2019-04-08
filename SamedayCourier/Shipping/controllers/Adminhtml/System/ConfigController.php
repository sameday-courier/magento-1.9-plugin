<?php

require_once(Mage::getBaseDir('lib') . '/samedaycourier-php-sdk/src/Sameday/autoload.php');
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml') . DS . 'System' . DS . 'ConfigController.php');

class SamedayCourier_Shipping_Adminhtml_System_ConfigController extends Mage_Adminhtml_System_ConfigController
{
    /**
     * Save configuration
     *
     */
    public function saveAction()
    {
        $session = Mage::getSingleton('adminhtml/session');
        /* @var $session Mage_Adminhtml_Model_Session */

        $groups = $this->getRequest()->getPost('groups');

        if (isset($_FILES['groups']['name']) && is_array($_FILES['groups']['name'])) {
            /**
             * Carefully merge $_FILES and $_POST information
             * None of '+=' or 'array_merge_recursive' can do this correct
             */
            foreach ($_FILES['groups']['name'] as $groupName => $group) {
                if (is_array($group)) {
                    foreach ($group['fields'] as $fieldName => $field) {
                        if (!empty($field['value'])) {
                            $groups[$groupName]['fields'][$fieldName] = array('value' => $field['value']);
                        }
                    }
                }
            }
        }

        try {
            if (!$this->_isSectionAllowed($this->getRequest()->getParam('section'))) {
                throw new Exception(Mage::helper('adminhtml')->__('This section is not allowed.'));
            }

            // custom save logic
            $groups = $this->saveSection($groups);
            $section = $this->getRequest()->getParam('section');
            $website = $this->getRequest()->getParam('website');
            $store   = $this->getRequest()->getParam('store');
            Mage::getSingleton('adminhtml/config_data')
                ->setSection($section)
                ->setWebsite($website)
                ->setStore($store)
                ->setGroups($groups)
                ->save();

            // reinit configuration
            Mage::getConfig()->reinit();
            Mage::dispatchEvent('admin_system_config_section_save_after', array(
                'website' => $website,
                'store'   => $store,
                'section' => $section
            ));
            Mage::app()->reinitStores();

            // website and store codes can be used in event implementation, so set them as well
            Mage::dispatchEvent("admin_system_config_changed_section_{$section}",
                array('website' => $website, 'store' => $store)
            );
            $session->addSuccess(Mage::helper('adminhtml')->__('The configuration has been saved.'));
        }
        catch (Mage_Core_Exception $e) {
            foreach(explode("\n", $e->getMessage()) as $message) {
                $session->addError($message);
            }
        }
        catch (Exception $e) {
            $session->addException($e,
                Mage::helper('adminhtml')->__('An error occurred while saving this configuration:') . ' '
                . $e->getMessage());
        }

        $this->_saveState($this->getRequest()->getPost('config_state'));

        $this->_redirect('*/*/edit', array('_current' => array('section', 'website', 'store')));
    }

    /**
     *  Custom save logic for section
     */
    protected function saveSection($groups)
    {
        $method = 'save' . uc_words($this->getRequest()->getParam('section'), '');
        if (method_exists($this, $method)) {
            return $this->$method($groups);
        }

        return;
    }

    /**
     * @return mixed
     */
    protected function saveCarriers($groups)
    {
        if (isset($groups['samedaycourier_shipping'])) {
            $user = $groups['samedaycourier_shipping']['fields']['user']['value'];
            $password = Mage::helper('core')->decrypt(Mage::getStoreConfig('carriers/samedaycourier_shipping/password', Mage::app()->getStore()));
            if ($groups['samedaycourier_shipping']['fields']['password']['value'] !== '******') {
                $password = $groups['samedaycourier_shipping']['fields']['password']['value'];
            }

            $is_testing = (bool) $groups['samedaycourier_shipping']['fields']['is_testing']['value'];

            $sameday = $this->initClient($user, $password, $is_testing);

            if (!$sameday->login()) {
                unset($groups['samedaycourier_shipping']);

                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('samedaycourier_shipping/data')->__('Connection with sameday was unsuccessful')
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('samedaycourier_shipping/data')->__('Connection with sameday was successful !')
                );
            }
        }

        return $groups;
    }

    /**
     * @param $user
     * @param $password
     * @param $is_testing
     * @return \Sameday\SamedayClient
     * @throws \Sameday\Exceptions\SamedaySDKException
     */
    protected function initClient($user, $password, $is_testing)
    {
        return new \Sameday\SamedayClient(
            $user,
            $password,
            $is_testing ? 'https://sameday-api.demo.zitec.com' : 'https://api.sameday.ro',
            'MAGENTO',
            '1.*'
        );
    }
}