<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\String;
use \Ext\File as F;

class File extends Element
{
    /**
     * @param \Ext\File $_file
     */
    public function setFile($_file)
    {
        $size = $_file->getSizeMeasure();

        $value = array(
            'name' => $_file->getName(),
            'path' => $_file->getPath(),
            'uri' => $_file->getUri(),
            'ext' => $_file->getExt(),
            'ext-uppercase' => String::toUpper($_file->getExt()),
            'size' => $size['string']
        );

        foreach (F::getLangs() as $lang) {
            if (isset($size["string-$lang"])) {
                $value["size-$lang"] = $size["string-$lang"];
            }
        }

        $this->setValue($value);
    }

    public function computeValue($_data)
    {
        $value = array();
        $name = $this->getName();

        if (
            is_array($_data) && !empty($_data[$name]) &&
            is_array($_data[$name]) && !empty($_data[$name]['name'])
        ) {
            $value = array(
                'name' => $_data[$name]['name'],
                'tmp_name' => $_data[$name]['tmp_name']
            );
        }

        return $value;
    }

    public function checkValue($_value = null)
    {
        $isUploaded = empty($_value) ||
                      !is_array($_value) ||
                      empty($_value['name']) ||
                      empty($_value['tmp_name']);

        if ($this->isRequired() && !$isUploaded) {
            return static::ERROR_REQUIRED;

        } else if (!$isUploaded) {
            return static::NO_UPDATE;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            $value = $this->getValue('name');
            if ($value) {
                return array($this->getName() => $value);
            }
        }

        return false;
    }
}
