<?php

class MagicToolbox_Sirv_Model_Varien_Image_Adapter_Gd2 extends Varien_Image_Adapter_Gd2 {

    public function save($destination = null, $newName = null) {

        if(!(int)Mage::getStoreConfig('sirv/general/enabled')) {
            return parent::save($destination, $newName);
        }

        $tempFileName = tempnam(sys_get_temp_dir(), 'sirv');
        parent::save($tempFileName);

        $fileName = !isset($destination) ? $this->_fileName : $destination;
        if(isset($destination) && isset($newName)) {
            $fileName = "{$destination}/{$fileName}";
        } elseif(isset($destination) && !isset($newName)) {
            $info = pathinfo($destination);
            $fileName = $destination;
            $destination = $info['dirname'];
        } elseif(!isset($destination) && isset($newName)) {
            $fileName = "{$this->_fileSrcPath}/{$newName}";
        } else {
            $fileName = $this->_fileSrcPath.$this->_fileSrcName;
        }

        if(Mage::getSingleton('sirv/adapter_s3')->save($fileName, $tempFileName)) {
            @unlink($tempFileName);
        } else {
            if(!is_writable($destination)) {
                try {
                    $io = new Varien_Io_File();
                    $io->mkdir($destination);
                } catch (Exception $e) {
                    throw new Exception("Unable to write file into directory '{$destinationDir}'. Access forbidden.");
                }
            }
            @rename($tempFileName, $fileName);
            @chmod($fileName, 0644);
        }

    }

}
