<?php

class MagicToolbox_Sirv_Model_Observer extends Mage_Core_Model_Abstract {

    public function cleanCache($schedule) {
        Mage::Helper('sirv/cache')->cleanCache();
    }

    public function onConfigChange($schedule) {

        $sirv = Mage::getSingleton('sirv/adapter_s3');

        if(!$sirv->isAuth()) {
            $session = Mage::getSingleton('adminhtml/session');
            $session->addWarning('The access identifiers you provided for Sirv were denied. You must enter proper credentials to use Sirv.');
            return false;
        }

        if(!$sirv->isEnabled()) {
            $session = Mage::getSingleton('adminhtml/session');
            $session->addWarning('The bucket name you provided ('.Mage::getStoreConfig('sirv/s3/bucket').') is not available. You must enter a proper bucket name to use Sirv.');
            return false;
        }

        if(!(int)Mage::getStoreConfig('sirv/general/enabled')) {
            return false;
        }

        //NOTE: clear file and DB cache
        Mage::Helper('sirv/cache')->clearCache();

        return true;
    }

}
