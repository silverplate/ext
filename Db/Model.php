<?php

namespace Ext\Db;

use \Ext\String;
use \Ext\Date;
use \Ext\Xml;
use \Ext\File;

class Model extends ActiveRecord
{
    /** @var \Ext\File[] */
    protected $_files;

    /** @var \Ext\Image[] */
    protected $_images;

    public function getTitle()
    {
        if (isset($this->title) && $this->title)    return $this->title;
        else if (isset($this->name) && $this->name) return $this->name;
        else                                        return 'ID ' . $this->id;
    }

    public function getDate($_name)
    {
        return !empty($this->$_name) ? Date::getDate($this->$_name) : false;
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $node = $_node ? $_node : String::dash($this->getTable());

        if (empty($_xml))         $xml = array();
        else if (is_array($_xml)) $xml = $_xml;
        else                      $xml = array($_xml);

        if (!array_key_exists('title', $xml)) {
            Xml::append($xml, Xml::cdata('title', $this->getTitle()));
        }

        $attrs = empty($_attrs) ? array() : $_attrs;

        if (!array_key_exists('id', $attrs)) {
            $attrs['id'] = $this->id;
        }

        return Xml::node($node, $xml, $attrs);
    }

    public function getBackOfficeXml($_xml = array(), $_attrs = array())
    {
        $attrs = $_attrs;

        if (
            !isset($attrs['is_published']) && (
                ($this->hasAttr('is_published') && $this->isPublished) ||
                ($this->hasAttr('status_id') && $this->statusId == 1)
            )
        ) {
            $attrs['is-published'] = 1;
        }

        return $this->getXml('item', $_xml, $attrs);
    }

    public function getFiles()
    {
        if (is_null($this->_files)) {
            $this->_files = array();

            if (
                method_exists($this, 'getFilePath') &&
                $this->getFilePath() &&
                is_dir($this->getFilePath())
            ) {
                $handle = opendir($this->getFilePath());

                while (false !== $item = readdir($handle)) {
                    $filePath = rtrim($this->getFilePath(), '/') . '/' . $item;

                    if ($item{0} != '.' && is_file($filePath)) {
                        $file = File::factory($filePath);

                        $this->_files[String::toLower($file->getFilename())] =
                            $file;
                    }
                }

                closedir($handle);
            }
        }

        return $this->_files;
    }

    public function getFileByFilename($_filename)
    {
        $files = $this->getFiles();

        return $files && array_key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getFileByName($_name)
    {
        foreach ($this->getFiles() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getFile($_name)
    {
        $file = $this->getFileByName($_name);

        if (!$file) {
            $file = $this->getFileByFilename($_name);
        }

        return $file;
    }

    public function getImages()
    {
        if (is_null($this->_images)) {
            $this->_images = array();

            foreach ($this->getFiles() as $key => $file) {
                if ($file->isImage()) {
                    $this->_images[$key] = $file;
                }
            }
        }

        return $this->_images;
    }

    public function getIlluByFilename($_filename)
    {
        $files = $this->getImages();

        return $files && array_key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getIlluByName($_name)
    {
        foreach ($this->getImages() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getIllu($_name)
    {
        $illu = $this->getIlluByName($_name);

        if (!$illu) {
            $illu = $this->getIlluByFilename($_name);
        }

        return $illu;
    }

    public function resetFiles()
    {
        $this->_files = null;
        $this->_images = null;
    }

    public function cleanFileCache()
    {
        foreach ($this->getFiles() as $file)
            File\Cache::delete($file->getPath());
    }

    public function uploadFile($_filename, $_tmpName, $_newName = null)
    {
        if (!method_exists($this, 'getFilePath')) {
            throw new \Exception('Method getFilePath must be implemented.');
        }

        $filename = is_null($_newName)
                  ? File::normalizeName($_filename)
                  : $_newName . '.' . File::computeExt($_filename);

        $path = $this->getFilePath() . $filename;

        File::deleteFile($path);
        File::createDir($this->getFilePath());

        move_uploaded_file($_tmpName, $path);
        File::chmod($path, 0777);

        File\Cache::delete($path);
    }

    public function delete()
    {
        foreach ($this->getFiles() as $item) {
            $item->delete();
        }

        return parent::delete();
    }
}
