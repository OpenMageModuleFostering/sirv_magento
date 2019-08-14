<?php

class MagicToolbox_Sirv_Model_Cache extends Mage_Core_Model_Abstract {

    protected function _construct() {
        parent::_construct();
        $this->_init('sirv/cache', 'id');
    }

}
