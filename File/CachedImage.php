<?php

namespace Ext\File;

use \Ext\File;
use \Ext\Image;

class CachedImage extends Image
{
    use CachedFileTrait {
        isCache as traitIsCache;
    }

    public function computeImageSize()
    {
        parent::computeImageSize();
        $this->cache();
    }

    public function initFromCache()
    {
        $cache = $this->getCache();

        if ($cache) {
            $this->setWidth($cache['width']);
            $this->setHeight($cache['height']);
            $this->setMime($cache['mime']);

            return true;
        }

        return false;
    }

    public function getWidth()
    {
        if (!isset($this->_width) && !$this->initFromCache()) {
            $this->computeImageSize();
        }

        return $this->_width;
    }

    public function getHeight()
    {
        if (!isset($this->_height) && !$this->initFromCache()) {
            $this->computeImageSize();
        }

        return $this->_height;
    }

    public function getMime()
    {
        if (!isset($this->_mime) && !$this->initFromCache()) {
            $this->computeImageSize();
        }

        return parent::getMime();
    }

    public function isCache($_isCache = null)
    {
        if ($_isCache === null) {
            return $this->traitIsCache($_isCache);

        } else if (!$this->traitIsCache($_isCache)) {
            $this->_width = null;
            $this->_height = null;
        }
    }

    public static function cropHeight(Image $_image, $_height)
    {
        $result = parent::cropHeight($_image, $_height);

        if ($result instanceof CachedImage) {
            $result->isCache(false);
        }

        return $result;
    }

    public static function resize($_src,
                                  $_dstWidth = null,
                                  $_dstHeight = null,
                                  $_dstFilePath = null,
                                  $_quality = 90)
    {
        $class = get_called_class();

        if ($_src instanceof CachedImage) {
            $src = $_src;

        } else if ($_src instanceof File) {
            /** @var File $_src */
            $src = new $class($_src->getPath());

        } else {
            $src = new $class($_src);
        }

        $src->isCache(false);

        $result = parent::resize(
            $src,
            $_dstWidth,
            $_dstHeight,
            $_dstFilePath,
            $_quality
        );

        if ($result) {
            static::deleteCache($result->getPath());
        }

        return $result;
    }

    public static function replaceWithGd($_dstImage, $_repl, $_backup = null)
    {
        $res = parent::replaceWithGd($_dstImage, $_repl, $_backup);
        if ($res) static::deleteCache($_dstImage);
        return $res;
    }

    public function save()
    {
        $res = parent::save();
        if ($res) static::deleteCache($this->getPath());
        return $res;
    }
}
