<?php

class MagicToolbox_Sirv_Model_Source_Network {

    public function toOptionArray() {
        return array(
            array('value' => 'CDN', 'label' => 'Sirv CDN (recommended)'),
            array('value' => 'DIRECT', 'label' => 'Sirv direct'),
        );
    }

}
