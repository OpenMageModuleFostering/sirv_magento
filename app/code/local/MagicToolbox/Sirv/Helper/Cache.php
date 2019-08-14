<?php

class MagicToolbox_Sirv_Helper_Cache extends Mage_Core_Helper_Abstract {

    private static $cacheStorage;
    private static $cacheTTL;
    private static $cacheFile;
    private static $cache = null;

    public function __construct() {
        self::$cacheStorage = intval(Mage::getStoreConfig('sirv/general/cache_storage'));
        self::$cacheTTL = floatval(Mage::getStoreConfig('sirv/general/cache_ttl')) * 60;//NOTE: in seconds
        self::$cacheFile = Mage::getBaseDir('cache').DS.'sirv.cache';
        if(self::$cacheStorage == 2) {//NOTE: file system cache
            $this->loadFileCache();
        }
    }

    protected function loadFileCache() {
        if(self::$cache == null) {
            if(file_exists(self::$cacheFile) && $contents = file_get_contents(self::$cacheFile)) {
                self::$cache = unserialize($contents);
            }
            if(!is_array(self::$cache)) {
                self::$cache = array();
            }
        }
    }

    public function isCached($url) {
        $cacheTTL = rand(intval(self::$cacheTTL * 0.9), intval(self::$cacheTTL * 1.1));
        if(self::$cacheStorage == 1) {//NOTE: database cache
            try {
                $model = Mage::getModel('sirv/cache')->load($url, 'url');
                $lastChecked = $model->getLastChecked();
                if(!$lastChecked) return false;
                $lastChecked = strtotime($lastChecked);
            } catch(Exception $e) {
                return false;
            }
            $maxTime = intval($lastChecked) + $cacheTTL;
            return (time() < $maxTime);
        } else if(self::$cacheStorage == 2) {//NOTE: file system cache
            if(array_key_exists($url, self::$cache)) {
                $maxTime = self::$cache[$url] + $cacheTTL;
                return (time() < $maxTime);
            } else {
                return false;
            }
        }
    }

    public function updateCache($url, $remove = false) {
        if(self::$cacheStorage == 1) {//NOTE: database cache
            try {
                $model = Mage::getModel('sirv/cache')->load($url, 'url');
                if($remove) {
                    $model->delete();
                } else {
                    $model->setUrl($url);
                    $model->setLastChecked(date('Y-m-d H:i:s'));
                    $model->save();
                }
            } catch(Exception $e) {
                throw new Exception("Could not access caching database table.");
                return false;
            }
            return true;
        } else if(self::$cacheStorage == 2) {//NOTE: file system cache
            self::loadFileCache();
            if($remove) {
                unset(self::$cache[$url]);
            } else {
                self::$cache[$url] = time();
            }
            file_put_contents(self::$cacheFile, serialize(self::$cache));
            return true;
        }
    }

    public function cleanCache() {
        if(!(int)Mage::getStoreConfig('sirv/general/enabled')) {
            return;
        }
        //NOTE: find and delete all expired entries
        if(self::$cacheStorage == 1) {//NOTE: database cache
            $minTime = time() - (self::$cacheTTL);
            $collection = Mage::getModel('sirv/cache')->getCollection()
                            ->addFieldToFilter('last_checked', array('lt' => date('Y-m-d H:i:s', $minTime)))
                            ->load();
            if($collection->getSize() > 0) {
                foreach($collection->getIterator() as $record) {
                    $record->delete();
                }
            }
        } else if(self::$cacheStorage == 2) {//NOTE: file system cache
            if(count(self::$cache)) {
                foreach(self::$cache as $url => $timeStamp) {
                    $maxTime = self::$cache[$url] + (self::$cacheTTL);
                    if(time() > $maxTime) {
                        unset(self::$cache[$url]);
                    }
                }
                file_put_contents(self::$cacheFile, serialize(self::$cache));
            }
        }
    }

    public function clearCache() {
        //NOTE: file cache
        file_put_contents(self::$cacheFile, '');
        //NOTE: DB cache
        Mage::getModel('sirv/cache')->getCollection()->truncate();
    }

}
