<?php

class MagicToolbox_Sirv_Model_Varien_Image extends Varien_Image {

    protected function _getAdapter($adapter = null) {
        if(!isset($this->_adapter)) {
            if((bool)Mage::getStoreConfig('sirv/general/sirv_image_processing')) {
                $this->_adapter = Mage::getModel('sirv/varien_image_adapter_sirv');
            } else {
                $this->_adapter = Mage::getModel('sirv/varien_image_adapter_gd2');
            }
        }
        return $this->_adapter;
    }

    public function getImagingOptionsQuery() {
        return $this->_getAdapter()->getImagingOptionsQuery();
    }

}
