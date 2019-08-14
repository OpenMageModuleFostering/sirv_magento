<?php

class MagicToolbox_Sirv_Model_Source_Cachestorage {

    public function toOptionArray() {
        return array(
            array('value' => 1, 'label' => 'Database'),
            array('value' => 2, 'label' => 'Filesystem'),
        );
    }

}
