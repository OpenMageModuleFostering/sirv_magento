<?php

class MagicToolbox_Sirv_Model_Mysql4_Cache_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('sirv/cache');
    }

    public function truncate() {
        if(method_exists($this->getConnection(), 'truncate')) {
            $this->getConnection()->truncate($this->getTable('sirv/cache'));
        } else {
            $sql = 'TRUNCATE TABLE '.$this->getConnection()->quoteIdentifier($this->getTable('sirv/cache'));
            $this->getConnection()->raw_query($sql);
        }
    }

}
