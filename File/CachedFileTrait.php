<?php

namespace Ext\File;

trait CachedFileTrait
{
    /** @var bool */
    protected $_isCache = true;

    /** @var int */
    protected $_size;

    public static function deleteFile($_filePath)
    {
        static::deleteCache($_filePath);
        return parent::deleteFile($_filePath);
    }

    public function getSize()
    {
        if (is_null($this->_size)) {
            $cache = $this->getCache();

            if ($cache) {
                $this->_size = $cache['size'];

            } else {
                $this->_size = filesize($this->getPath());
                if (!($this instanceof Image)) $this->cache();
            }
        }

        return $this->_size;
    }

    /**
     * @return array
     */
    public function getCache()
    {
        return Cache::getCache($this->getPath());
    }

    public static function deleteCache($_filePath)
    {
        return Cache::delete($_filePath);
    }

    public function cache()
    {
        return $this->isCache() ? Cache::saveFile($this) : null;
    }

    public function isCache($_isCache = null)
    {
        if ($_isCache === null) {
            return (bool) $this->_isCache;

        } else {
            $this->_isCache = (bool) $_isCache;
            if (!$this->_isCache) $this->_size = null;
        }
    }
}
