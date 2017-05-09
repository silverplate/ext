<?php

namespace Ext;

use \Ext\File;
use \Ext\Xml;

class Image extends File
{
    /** @var int */
    protected $_width;

    /** @var int */
    protected $_height;

    /** @var string */
    protected $_text;

    /** @var resource */
    protected $_gd;

    public function computeImageSize()
    {
        if ($this->getSize() > 0) {
            $size = getimagesize($this->getPath());
            $this->_width = $size[0];
            $this->_height = $size[1];
            $this->_mime = $size['mime'];

        } else {
            $this->_width = 0;
            $this->_height = 0;
            $this->_mime = '';
        }
    }

    public function getWidth()
    {
        if (!isset($this->_width)) $this->computeImageSize();
        return $this->_width;
    }

    public function setWidth($_width)
    {
        $this->_width = (int) $_width;
    }

    public function getHeight()
    {
        if (!isset($this->_height)) $this->computeImageSize();
        return $this->_height;
    }

    public function setHeight($_height)
    {
        $this->_height = (int) $_height;
    }

    public function getMime()
    {
        if (!isset($this->_mime)) $this->computeImageSize();
        return parent::getMime();
    }

    public function setMime($_mime)
    {
        $this->_mime = $_mime;
    }

    public function getText()
    {
        return $this->_text;
    }

    public function setText($_text)
    {
        $this->_text = $_text;
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $attrs = array(
            'width' => $this->getWidth(),
            'height' => $this->getHeight()
        );

        if ($_attrs) {
            $attrs = array_merge($attrs, $_attrs);
        }

        $xml = is_array($_xml) ? $_xml : array($_xml);

        if ($this->getText()) {
            $xml[] = Xml::cdata('text', $this->getText());
        }

        return parent::getXml(empty($_node) ? 'image' : $_node, $xml, $attrs);
    }

    public function getNode($_dom, $_name = null, $_attrs = null)
    {
        $node = parent::getNode(
            $_dom,
            empty($_name) ? 'image' : $_name,
            $_attrs
        );

        $node->setAttribute('width', $this->getWidth());
        $node->setAttribute('height', $this->getHeight());

        if ($this->getText()) {
            $node->appendChild($_dom->createCDATASection($this->getText()));
        }

        return $node;
    }

    public static function resize($_srcImage,
                                  $_dstWidth = null,
                                  $_dstHeight = null,
                                  $_dstFilePath = null,
                                  $_quality = 90)
    {
        if (empty($_dstWidth) && empty($_dstHeight)) {
            throw new \Exception('Destination width or height must be set.');
        }

        $class = get_called_class();

        if ($_srcImage instanceof Image) {
            $srcImage = $_srcImage;

        } else if ($_srcImage instanceof File) {
            $srcImage = new $class($_srcImage->getPath());

        } else {
            $srcImage = new $class($_srcImage);
        }

        $srcFilePath  = $srcImage->getPath();
        $srcExtension = $srcImage->getExt();
        $srcWidth     = $srcImage->getWidth();
        $srcHeight    = $srcImage->getHeight();
        $src          = $srcImage->getGd();

        if (empty($src)) {
            throw new \Exception('Unknown image type.');
        }

        $dstWidth = empty($_dstWidth)
                  ? $_dstHeight / $srcHeight * $srcWidth
                  : $_dstWidth;
        $dstHeight = empty($_dstHeight)
                   ? $_dstWidth / $srcWidth * $srcHeight
                   : $_dstHeight;
        $dstFilePath = empty($_dstFilePath)
                     ? $srcFilePath
                     : $_dstFilePath;
        $dstFileInfo = pathinfo($dstFilePath);

        // Если исходное изображение больше
        // хотя бы по одной стороне
        if ($srcWidth > $dstWidth || $srcHeight > $dstHeight) {
            $srcRate = $srcWidth / $srcHeight;
            $dstRate = $dstWidth / $dstHeight;
            $cropWidth = $srcWidth;
            $cropHeight = $srcHeight;
            $cropX = 0;
            $cropY = 0;

            // Если отношение ширины к высоте будущего
            // изображения больше, чем у предыдущего
            if ($dstRate > $srcRate) {
                $cropHeight = $srcWidth / $dstRate;
                $cropY = floor(($srcHeight - $cropHeight) / 2);

            // Если меньше
            } else if ($dstRate < $srcRate) {
                $cropWidth = $srcHeight * $dstRate;
                $cropX = floor(($srcWidth - $cropWidth) / 2);
            }

            // Если соотношения отличаются,
            // то нужно обрезать изображение
            if ($cropWidth != $srcWidth || $cropHeight != $srcHeight) {
                $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);
                imagecopy(
                    $croppedImage, $src,
                    0, 0,
                    $cropX, $cropY,
                    $cropWidth, $cropHeight
                );
                $src = $croppedImage;
                $srcWidth = $cropWidth;
                $srcHeight = $cropHeight;
            }

            $newImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled(
                $newImage, $src,
                0, 0,
                0, 0,
                $dstWidth, $dstHeight,
                $srcWidth, $srcHeight
            );

            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' .
                                   $dstFileInfo['filename'] . '.jpg';
            static::createDir($dstFileInfo['dirname']);
            imagejpeg($newImage, $dstFilePathWithType, $_quality);
            static::chmod($dstFilePathWithType, 0777);

            if (
                is_null($_dstFilePath) &&
                $dstFilePathWithType != $srcFilePath
            ) {
                static::deleteFile($srcFilePath);
            }

            return new $class($dstFilePathWithType);

        // Исходное изображение меньше,
        // поэтому ничего не делаем
        } else {
            $dstFilePathWithType = $dstFileInfo['dirname'] . '/' .
                                   $dstFileInfo['filename'] . '.' .
                                   $srcExtension;

            if (!is_file($dstFilePathWithType)) {
                static::createDir($dstFileInfo['dirname']);
                copy($srcFilePath, $dstFilePathWithType);
                static::chmod($dstFilePathWithType, 0777);
            }

            return new $class($dstFilePathWithType);
        }
    }

    /**
     * @param Image $_image
     * @param int $_height
     * @return Image|bool
     */
    public static function cropHeight(Image $_image, $_height)
    {
        if ($_image->getHeight() > $_height) {
            $cropped = imagecreatetruecolor($_image->getWidth(), $_height);

            imagecopy(
                $cropped, $_image->getGd(),
                0, 0,
                0, floor(($_image->getHeight() - $_height) / 2),
                $_image->getWidth(), $_height
            );

            /** @var Image $image */
            $class = get_called_class();
            $image = new $class($_image->getPath());
            $image->setGd($cropped);

            if ($image->save()) {
                return $image;
            }
        }

        return false;
    }

    /**
     * @param string $_dstImage
     * @param resource $_repl
     * @param string|bool $_backup
     * @return bool
     */
    public static function replaceWithGd($_dstImage, $_repl, $_backup = null)
    {
        if ($_backup) {
            if ($_backup === true) {
                $path = pathinfo($_dstImage);
                $backup = $path['dirname'] . '/' .
                          $path['filename'] . '-origin.' . $path['extension'];
            } else {
                $backup = $_backup;
            }

            // Проверка, чтобы не перезаписать самый первый бэкап,
            // который может содержать самую правильную версию.
            if (!is_file($backup)) {
                copy($_dstImage, $backup);
                static::chmod($backup, 0777);
            }
        }

        $result = imagejpeg($_repl, $_dstImage, 100);
        static::chmod($_dstImage, 0777);

        return $result;
    }

    /**
     * @param Image|string $_srcImage
     * @param string $_stampPath
     * @param string $_pos
     * @return resource
     */
    public static function getWatermark($_srcImage, $_stampPath, $_pos = null)
    {
        $src = $_srcImage instanceof Image ? $_srcImage : new Image($_srcImage);
        $wtrmrk = new Image($_stampPath);

        if ($_pos == 'center') {
            $x = round(($src->getWidth() - $wtrmrk->getWidth()) / 2);
            $y = round(($src->getHeight() - $wtrmrk->getHeight()) / 2);

        } else {
            $margin = array(0, 20, 15, 0);
            $x = $src->getWidth() - $wtrmrk->getWidth() - $margin[1];
            $y = $src->getHeight() - $wtrmrk->getHeight() - $margin[2];
        }

        imagecopy(
            $src->getGd(),
            $wtrmrk->getGd(),
            $x,
            $y,
            0,
            0,
            $wtrmrk->getWidth(),
            $wtrmrk->getHeight()
        );

        return $src->getGd();
    }

    /**
     * @param resource $_gd
     */
    public function setGd($_gd)
    {
        $this->_gd = $_gd;
    }

    /**
     * @return resource
     */
    public function getGd()
    {
        if (is_null($this->_gd)) {
            switch (str_replace('image/', '', $this->getMime())) {
                case 'jpeg':
                case 'jpg':
                    $this->setGd(imagecreatefromjpeg($this->getPath()));
                    break;

                case 'png':
                    $this->setGd(imagecreatefrompng($this->getPath()));
                    break;

                case 'gif':
                    $this->setGd(imagecreatefromgif($this->getPath()));
                    break;

                default:
                    $this->setGd(imagecreatefromgd($this->getPath()));
            }
        }

        return $this->_gd;
    }

    public function save()
    {
        if (in_array($this->getExt(), array('jpeg', 'jpg', 'png', 'gif'))) {
            static::createDir($this->getDir());

            switch ($this->getExt()) {
                case 'jpeg':
                case 'jpg':
                    imagejpeg($this->getGd(), $this->getPath(), 90);
                    break;

                case 'png':
                    imagepng($this->getGd(), $this->getPath());
                    break;

                case 'gif':
                    imagegif($this->getGd(), $this->getPath());
                    break;
            }

            static::chmod($this->getPath(), 0777);
            return true;
        }

        throw new \Exception('Can save only jpeg, png or gif files.');
    }
}
