<?php

class MagicToolbox_Sirv_Model_Varien_Image_Adapter_Sirv extends Varien_Image_Adapter_Abstract {

    protected $_requiredExtensions = array('curl');

    protected $_resized = false;

    protected $_imaging_options = array();

    protected $_quality = null;

    //NOTE: called from the constructor of Varien_Image
    public function open($filename) {
        $this->_fileName = $filename;
        $this->getMimeType();
        $this->_getFileAttributes();
        if($this->_isMemoryLimitReached()) {
            throw new Varien_Exception('Memory limit has been reached.');
        }
    }

    protected function _isMemoryLimitReached() {
        $limit = $this->_convertToByte(ini_get('memory_limit'));
        return (memory_get_usage(true)) > $limit;
    }

    protected function _convertToByte($memoryValue) {
        if(stripos($memoryValue, 'M') !== false) {
            return (int)$memoryValue * 1024 * 1024;
        } else if(stripos($memoryValue, 'KB') !== false) {
            return (int)$memoryValue * 1024;
        }
        return (int)$memoryValue;
    }

    public function save($destination=null, $newName=null) {

        $fileName = !isset($destination) ? $this->_fileName : $destination;

        if(isset($destination) && isset($newName)) {
            $fileName = "{$destination}/{$fileName}";
        } elseif(isset($destination) && !isset($newName)) {
            $fileName = $destination;
        } elseif(!isset($destination) && isset($newName)) {
            $fileName = "{$this->_fileSrcPath}/{$newName}";
        } else {
            $fileName = $this->_fileSrcPath.$this->_fileSrcName;
        }

        $sirvFileName = str_replace(Mage::getBaseDir('media'), '', $fileName);

        Mage::getSingleton('sirv/adapter_s3')->save($sirvFileName, $fileName);

    }

    public function display() {
        throw new Varien_Exception('Direct output not supported yet.');
    }

    public function quality($quality = null) {

        if(!is_null($quality)) {
            //NOTE: set quality param for JPG file type
            if($this->_fileType == IMAGETYPE_JPEG) {
                $this->setImagingOptions('quality', (int)$quality);
            } else
            //NOTE: set quality param for PNG file type
            if($this->_fileType == IMAGETYPE_PNG) {
                $quality = round(((int)$quality / 100) * 10);
                if($quality < 1) {
                    $quality = 1;
                } else if($quality > 10) {
                    $quality = 10;
                }
                $quality = 10 - $quality;
                $this->setImagingOptions('png.compression', $quality);
            }

        }
        return $quality;

    }

    private function _fillBackgroundColor() {
        list($r, $g, $b) = $this->_backgroundColor;
        $this->setImagingOptions('canvas.color', dechex($r).dechex($g).dechex($b));
    }

    public function resize($frameWidth = null, $frameHeight = null) {
        if(empty($frameWidth) && empty($frameHeight)) {
            throw new Exception('Invalid image dimensions.');
        }

        //NOTE: calculate lacking dimension
        if(!$this->_keepFrame) {
            if(null === $frameWidth) {
                $frameWidth = round($frameHeight * ($this->_imageSrcWidth / $this->_imageSrcHeight));
            } else if(null === $frameHeight) {
                $frameHeight = round($frameWidth * ($this->_imageSrcHeight / $this->_imageSrcWidth));
            }
        } else {
            if(null === $frameWidth) {
                $frameWidth = $frameHeight;
            } else if(null === $frameHeight) {
                $frameHeight = $frameWidth;
            }
        }

        //NOTE: define coordinates of image inside new frame
        $srcX = 0;
        $srcY = 0;
        $dstX = 0;
        $dstY = 0;
        $dstWidth  = $frameWidth;
        $dstHeight = $frameHeight;
        if($this->_keepAspectRatio) {
            //NOTE: do not make picture bigger, than it is, if required
            if($this->_constrainOnly) {
                if(($frameWidth >= $this->_imageSrcWidth) && ($frameHeight >= $this->_imageSrcHeight)) {
                    $dstWidth  = $this->_imageSrcWidth;
                    $dstHeight = $this->_imageSrcHeight;
                }
            }
            //NOTE: keep aspect ratio
            if($this->_imageSrcWidth / $this->_imageSrcHeight >= $frameWidth / $frameHeight) {
                $dstHeight = round(($dstWidth / $this->_imageSrcWidth) * $this->_imageSrcHeight);
            } else {
                $dstWidth = round(($dstHeight / $this->_imageSrcHeight) * $this->_imageSrcWidth);
            }
        }
        //NOTE: define position in center (TODO: add positions option)
        $dstY = round(($frameHeight - $dstHeight) / 2);
        $dstX = round(($frameWidth - $dstWidth) / 2);

        //NOTE: get rid of frame (fallback to zero position coordinates)
        if(!$this->_keepFrame) {
            $frameWidth  = $dstWidth;
            $frameHeight = $dstHeight;
            $dstY = 0;
            $dstX = 0;
        } else {
            $this->setImagingOptions('canvas.width', $frameWidth);
            $this->setImagingOptions('canvas.height', $frameHeight);
            $this->_fillBackgroundColor();
        }

        $this->setImagingOptions('scale.width', $dstWidth);
        $this->setImagingOptions('scale.height', $dstHeight);

        $this->_resized = true;
    }

    public function rotate($angle) {
        $angle = (int)$angle;
        if($angle <= 0) {
            return;
        }
        if($angle > 360) {
            $angle = $angle - floor($angle/360)*360;
        }
        if($angle <= 180) {
            $angle = -1*$angle;
        } else {
            $angle = 360-$angle;
        }
        $this->setImagingOptions('rotate', $angle);
        $this->_fillBackgroundColor();
    }

    public function watermark($watermarkImage, $positionX=0, $positionY=0, $watermarkImageOpacity=30, $repeat=false) {

        $sirvFileName = str_replace(Mage::getBaseDir('media'), '', $watermarkImage);
        $sirv = Mage::getSingleton('sirv/adapter_s3');
        if(!$sirv->fileExists($sirvFileName)) {
            $sirv->save($sirvFileName, $watermarkImage);
        }

        list($watermarkSrcWidth, $watermarkSrcHeight, $watermarkFileType, ) = getimagesize($watermarkImage);
        $this->_getFileAttributes();

        $width = $this->getWatermarkWidth();
        if(empty($width)) $width = $watermarkSrcWidth;
        $height = $this->getWatermarkHeigth();
        if(empty($height)) $height = $watermarkSrcHeight;
        $opacity = $this->getWatermarkImageOpacity();
        if(empty($opacity)) $opacity = 50;

        $this->setImagingOptions('watermark.image', urlencode($sirv->getRelUrl($sirvFileName)));
        
        $this->setImagingOptions('watermark.opacity', $opacity);

        if($this->getWatermarkWidth() && $this->getWatermarkHeigth() && ($this->getWatermarkPosition() != self::POSITION_STRETCH)) {
            $this->setImagingOptions('watermark.scale.width', $width);
            $this->setImagingOptions('watermark.scale.height', $height);
            $this->setImagingOptions('watermark.scale.option', 'ignore');
        }

        if($this->getWatermarkPosition() == self::POSITION_TILE ) {
            $this->setImagingOptions('watermark.position', 'tile');
        } elseif( $this->getWatermarkPosition() == self::POSITION_STRETCH ) {
            $this->setImagingOptions('watermark.position', 'center');
            $this->setImagingOptions('watermark.scale.width', $this->_imageSrcWidth);
            $this->setImagingOptions('watermark.scale.height', $this->_imageSrcHeight);
            $this->setImagingOptions('watermark.scale.option', 'ignore');
        } elseif( $this->getWatermarkPosition() == self::POSITION_CENTER ) {
            $this->setImagingOptions('watermark.position', 'center');
        } elseif( $this->getWatermarkPosition() == self::POSITION_TOP_RIGHT ) {
            $this->setImagingOptions('watermark.position', 'northeast');
        } elseif( $this->getWatermarkPosition() == self::POSITION_TOP_LEFT  ) {
            $this->setImagingOptions('watermark.position', 'northwest');
        } elseif( $this->getWatermarkPosition() == self::POSITION_BOTTOM_RIGHT ) {
            $this->setImagingOptions('watermark.position', 'southeast');
        } elseif( $this->getWatermarkPosition() == self::POSITION_BOTTOM_LEFT ) {
            $this->setImagingOptions('watermark.position', 'southwest');
        }

    }

    public function crop($top=0, $left=0, $right=0, $bottom=0) {

        if($left == 0 && $top == 0 && $right == 0 && $bottom == 0) {
            return;
        }

        $newWidth = $this->_imageSrcWidth - $left - $right;
        $newHeight = $this->_imageSrcHeight - $top - $bottom;

        $this->setImagingOptions('crop.x', (int)$top);
        $this->setImagingOptions('crop.y', (int)$left);
        $this->setImagingOptions('crop.width', (int)$newWidth);
        $this->setImagingOptions('crop.height', (int)$newHeight);

    }

    public function checkDependencies() {
        foreach($this->_requiredExtensions as $value) {
            if(!extension_loaded($value)) {
                throw new Exception("Required PHP extension '{$value}' was not loaded.");
            }
        }
    }

    private function setImagingOptions($name, $value) {
        $this->_imaging_options[$name] = $value;
    }

    public function getImagingOptionsQuery() {
        $query = array();
        foreach($this->_imaging_options as $key => $value) {
            $query[] = "{$key}={$value}";
        }
        return empty($query) ? '' : '?'.implode('&', $query);
    }

}
