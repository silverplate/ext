<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\File;

class SystemName extends Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == static::SUCCESS &&
            $_value != '' &&
            !File::checkName($_value)
        ) {
            return static::ERROR_SPELLING;
        }

        return $status;
    }
}
