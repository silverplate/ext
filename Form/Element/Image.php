<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\File as F;
use \Ext\Str as Str;

class Image extends File
{
    /**
     * @param \Ext\Image $_file
     */
    public function setFile($_file)
    {
        $size = $_file->getSizeMeasure();

        $value = array(
            'name' => $_file->getName(),
            'path' => $_file->getPath(),
            'uri' => $_file->getUri(),
            'ext' => $_file->getExt(),
            'ext-uppercase' => Str::toUpper($_file->getExt()),
            'size' => $size['string'],
            'width' => $_file->getWidth(),
            'height' => $_file->getHeight()
        );

        foreach (F::getLangs() as $lang) {
            if (isset($size["string-$lang"])) {
                $value["size-$lang"] = $size["string-$lang"];
            }
        }

        $this->setValue($value);
    }
}
