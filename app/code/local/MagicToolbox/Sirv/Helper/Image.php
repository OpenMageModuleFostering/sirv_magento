<?php

class MagicToolbox_Sirv_Helper_Image extends Mage_Catalog_Helper_Image {

    public function __toString() {
        if(!(bool)Mage::getStoreConfig('sirv/general/enabled')) {
            return parent::__toString();
        }
        if(!(bool)Mage::getStoreConfig('sirv/general/sirv_image_processing')) {
            //1
            return parent::__toString();
            //2
            //parent::__toString();
            //return $this->_getModel()->getUrl();
        }
        try {
            $model = $this->_getModel();

            if($this->getImageFile()) {
                $model->setBaseFile($this->getImageFile());
            } else {
                $model->setBaseFile($this->getProduct()->getData($model->getDestinationSubdir()));
            }

            if($this->_scheduleRotate) {
                $model->rotate($this->getAngle());
            }

            if($this->_scheduleResize) {
                $model->resize();
            }

            if($this->getWatermark()) {
                $model->setWatermark($this->getWatermark());
            }

            if($model->isCached()) {
                $url = $model->getUrl();
            } else {

                $url = $model->saveFile()->getUrl();
            }
        } catch (Exception $e) {
            $url = Mage::getDesign()->getSkinUrl($this->getPlaceholder());
        }
        return $url;
    }

}
