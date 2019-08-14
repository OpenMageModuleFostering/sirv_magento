<?php

class MagicToolbox_Sirv_Model_Source_Cachestorage {

    public function toOptionArray() {
        return array(
            array('value' => 1, 'label' => 'in database'),
            array('value' => 2, 'label' => 'in filesystem'),
        );
    }

}
