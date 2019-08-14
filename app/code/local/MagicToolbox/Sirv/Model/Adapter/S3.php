<?php

class MagicToolbox_Sirv_Model_Adapter_S3 extends Varien_Object {

    private $sirv = null;
    private $auth = false;
    private $enabled = false;
    private $bucket = '';
    private $base_url = '';
    private $image_folder = '';

    protected function _construct() {
        if(is_null($this->sirv)) {
            $this->bucket = Mage::getStoreConfig('sirv/s3/bucket');
            //$this->base_url = Mage::app()->getStore()->isCurrentlySecure() ? "https://{$this->domain_name}.sirv.com" : "http://{$this->domain_name}.sirv.com";
            $this->base_url = "https://".$this->bucket.( (Mage::getStoreConfig('sirv/general/network')=='CDN')?'-cdn':'' ).".sirv.com";
            $this->image_folder = '/'.Mage::getStoreConfig('sirv/general/image_folder');
            $this->sirv = Mage::getModel('sirv/adapter_s3_wrapper', array(
                'host' => 's3.sirv.com',
                'bucket' => $this->bucket,
                'key' => Mage::getStoreConfig('sirv/s3/key'),
                'secret' => Mage::getStoreConfig('sirv/s3/secret'),
            ));
            $bucketsList = $this->sirv->listBuckets();
            if(!empty($bucketsList)) {
                $this->auth = true;
            }
            if(!empty($this->bucket) && in_array($this->bucket, $bucketsList)) {
                $this->enabled = true;
            }
        }
    }

    public function isAuth() {
        return $this->auth;
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function clearCache() {

        if(!$this->auth) return;

        $collection = Mage::getModel('sirv/cache')->getCollection();
        $collectionSize = $collection->getSize();

        if($collectionSize) {
            $pageNumber = 1;
            $pageSize = 1000;
            $lastPageNumber = ceil($collectionSize/$pageSize);
            do {
                $collection->setCurPage($pageNumber)->setPageSize($pageSize);
                $urls = array();
                foreach($collection->getIterator() as $record) {
                    $urls[] = $this->image_folder.$record->getData('url');
                }
                try {
                    $this->sirv->deleteMultipleObjects($urls);
                } catch(Exception $e) {

                }
                $collection->clear();
                $pageNumber++;
            } while($pageNumber <= $lastPageNumber);
        }

        Mage::Helper('sirv/cache')->clearCache();

    }

    public function save($destFileName, $srcFileName) {
        if(!$this->auth) return false;
        $destFileName = $this->getRelative($destFileName);
        try {
            $result = $this->sirv->uploadFile($this->image_folder.$destFileName, $srcFileName, true);
        } catch(Exception $e) {
            $result = false;
        }
        if($result) {
            Mage::Helper('sirv/cache')->updateCache($destFileName);
        }
        return $result;
    }

    public function remove($fileName) {
        if(!$this->auth) return false;
        $fileName = $this->getRelative($fileName);
        try {
            $result = $this->sirv->deleteObject($this->image_folder.$fileName);
        } catch(Exception $e) {
            $result = false;
        }
        if($result) {
            Mage::Helper('sirv/cache')->updateCache($fileName, true);
        }
        return $result;
    }

    public function getUrl($fileName) {
        return $this->base_url.$this->image_folder.$this->getRelative($fileName);
    }

    public function getRelUrl($fileName) {
        return $this->image_folder.$this->getRelative($fileName);
    }

    public function fileExists($fileName) {

        $fileName = $this->getRelative($fileName);

        $cached = Mage::Helper('sirv/cache')->isCached($fileName);
        if($cached) {
            return true;
        }

        $url = $this->getUrl($fileName);
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HEADER, true);
        curl_setopt($c, CURLOPT_NOBODY, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        //NOTE: for test to see if file size greater than zero
        //$size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($c);

        if($code == 200/* && $size*/) {
            Mage::Helper('sirv/cache')->updateCache($fileName);
            return true;
        } else {
            return false;
        }
    }

    public function getRelative($fileName) {
        $base = str_replace('\\', '/', Mage::getBaseDir('media'));
        $fileName = str_replace('\\', '/', $fileName);
        return str_replace($base, '', $fileName);
    }

}
