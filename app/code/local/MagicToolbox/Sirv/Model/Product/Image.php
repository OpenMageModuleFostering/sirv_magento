<?php

class MagicToolbox_Sirv_Model_Product_Image extends Mage_Catalog_Model_Product_Image {

    protected $_isSirvEnable = false;
    protected $_useSirvImageProcessing = true;

    protected function _construct() {
        parent::_construct();
        $this->_isSirvEnable = (bool)Mage::getStoreConfig('sirv/general/enabled');
        $this->_useSirvImageProcessing = (bool)Mage::getStoreConfig('sirv/general/sirv_image_processing');
    }

    public function getImageProcessor() {
        if(!$this->_processor && $this->_isSirvEnable) {
            $this->_processor = Mage::getModel('sirv/varien_image', $this->getBaseFile());
        }
        return parent::getImageProcessor();
    }

    public function saveFile() {
        if($this->_isSirvEnable && $this->_useSirvImageProcessing) {
            $fileName = $this->getBaseFile();
            $this->getImageProcessor()->save($fileName);
            Mage::helper('core/file_storage_database')->saveFile($fileName);
            return $this;
        }
        return parent::saveFile();
    }

    public function getUrl() {
        if($this->_isSirvEnable) {
            if($this->_useSirvImageProcessing) {
                $url = Mage::getSingleton('sirv/adapter_s3')->getUrl($this->_baseFile);
                $url .= $this->getImageProcessor()->getImagingOptionsQuery();
            } else {
                $url = Mage::getSingleton('sirv/adapter_s3')->getUrl($this->_newFile);
            }
            return $url;
        }
        return parent::getUrl();
    }

    public function isCached() {
        if($this->_isSirvEnable) {
            if($this->_useSirvImageProcessing) {
                $sirvFileName = str_replace(Mage::getBaseDir('media'), '', $this->_baseFile);
                return Mage::getSingleton('sirv/adapter_s3')->fileExists($sirvFileName);
            } else {
                return Mage::getSingleton('sirv/adapter_s3')->fileExists($this->_newFile);
            }
        }
        return parent::isCached();
    }

    public function clearCache() {
        parent::clearCache();
        if($this->_isSirvEnable) {
            Mage::getSingleton('sirv/adapter_s3')->clearCache();
        }
    }

}
