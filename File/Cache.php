<?php

namespace Ext\File;

use \Ext\File;
use \Ext\Image;
use \Ext\Db;

class Cache
{
    /** @var int Срок годности неделя. */
    const EXPIRE = 604800;

    /** @var bool */
    public static $isEnabled = true;

    /**
     * @var array[]
     */
    private static $_cache;

    /**
     * @return array[]
     */
    public static function get()
    {
        if (!static::$isEnabled) return false;

        if (is_null(static::$_cache)) {
            static::$_cache = static::load();
        }

        return static::$_cache;
    }

    public static function getDataTable()
    {
        return Db::get()->getPrefix() . 'file_cache';
    }

    /**
     * @return array[]
     */
    public static function load()
    {
        if (!static::$isEnabled) return false;

        static::cleanExpired();
        $map = array();
        $tbl = static::getDataTable();

        // ORDER_BY-конструкция добавлена, чтобы наверняка брались последние
        // данные по файлу. Уникальность с поля file_path снята, так как
        // часты случаи попытки добавить запись повторно (видимо, из-за
        // одновременных запросов).

        $data = Db::get()->getList(
            "SELECT * FROM `$tbl` ORDER BY `creation_time`"
        );

        foreach ($data as $item) {
            $map[$item['file_path']] = $item;
        }

        return $map;
    }

    /**
     * @return \mysqli_result
     */
    public static function clean()
    {
        if (!static::$isEnabled) return false;

        static::$_cache = null;
        return Db::get()->execute('TRUNCATE ' . static::getDataTable());
    }

    /**
     * @todo Хорошо бы сбрасывать auto_increment счетчики?
     * @return \mysqli_result
     */
    public static function cleanExpired()
    {
        if (!static::$isEnabled) return false;

        static::$_cache = null;

        $tbl = static::getDataTable();
        $past = time() - static::EXPIRE;

        return Db::get()->execute(
            "DELETE FROM `$tbl` WHERE `creation_time` <= $past"
        );
    }

    /**
     * @param string $_path
     * @return \mysqli_result|bool
     */
    public static function delete($_path)
    {
        if (!static::$isEnabled) return false;

        $instance = static::getCache($_path);

        if ($instance) {
            $tbl = static::getDataTable();
            $key = Db::escape($instance['file_path']);

            unset(static::$_cache[$_path]);
            return Db::get()->execute(
                "DELETE FROM `$tbl` WHERE `file_path` = $key"
            );

        } else {
            return false;
        }
    }

    /**
     * @param string $_path
     * @return array|bool
     */
    public static function getCache($_path)
    {
        if (!static::$isEnabled) return false;

        static::get();
        return array_key_exists($_path, static::$_cache)
             ? static::$_cache[$_path]
             : false;
    }

    /**
     * @param string $_filePath
     * @param int $_size
     * @param int $_width
     * @param int $_height
     * @param string $_mime
     * @return array|bool
     */
    public static function save(
        $_filePath,
        $_size,
        $_width = null,
        $_height = null,
        $_mime = null
    )
    {
        if (!static::$isEnabled) return false;

        $size = (int) $_size;
        if ($size == 0) return false;

        $instance = array(
            'size' => $_size,
            'file_path' => $_filePath,
            'creation_time' => time()
        );

        $width = (int) $_width;
        $height = (int) $_height;

        if ($width > 0 && $height > 0) {
            $instance['width'] = $width;
            $instance['height'] = $height;
        }

        if ($_mime) {
            $instance['mime'] = $_mime;
        }

        static::delete($instance['file_path']);

        Db::get()->execute(
            'INSERT INTO ' . static::getDataTable() .
            Db::get()->getQueryFields($instance, 'insert')
        );

        $instance[static::getDataTable() . '_id'] =
            Db::get()->getLastInsertedId();
        static::$_cache[$instance['file_path']] = $instance;

        return $instance;
    }

    /**
     * @param CachedFileTrait $_file
     * @return array|bool
     */
    public static function saveFile($_file)
    {
        if (!static::$isEnabled) return false;

        if ($_file instanceof Image) {
            return static::save(
                $_file->getPath(),
                $_file->getSize(),
                $_file->getWidth(),
                $_file->getHeight(),
                $_file->getMime()
            );

        } else {
            return static::save($_file->getPath(), $_file->getSize());
        }
    }

    /**
     * @param string $_path
     * @return File|Image|bool
     */
    public static function getFile($_path)
    {
        if (!static::$isEnabled) return false;

        $instance = static::getCache($_path);

        if (
            $instance &&
            !empty($instance['file_path']) &&
            is_file($instance['file_path'])
        ) {
            if (File::isImageExt(File::computeExt($instance['file_path']))) {
                $file = new CachedImage;

                if (!empty($instance['width']) && !empty($instance['height'])) {
                    $file->setWidth($instance['width']);
                    $file->setHeight($instance['height']);
                }

            } else {
                $file = new CachedFile;
            }

            $file->setPath($instance['file_path']);
            $file->setSize($instance['size']);

            if (!empty($instance['mime'])) {
                $file->setMime($instance['mime']);
            }

            return $file;
        }

        return false;
    }
}
