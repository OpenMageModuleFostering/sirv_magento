<?php

class MagicToolbox_Sirv_Model_Category extends Mage_Catalog_Model_Category {

    public function getImageUrl() {
        if((int)Mage::getStoreConfig('sirv/general/enabled') && ($image = $this->getImage())) {
            $relPath = '/catalog/category/'.$image;
            $sirv = Mage::getSingleton('sirv/adapter_s3');
            if(!$sirv->fileExists($relPath)) {
                $sirv->save($relPath, Mage::getBaseDir('media').$relPath);
            }
            $url = $sirv->getUrl($relPath);
            if($url) {
                return $url;
            }
        }
        return parent::getImageUrl();
    }

}
