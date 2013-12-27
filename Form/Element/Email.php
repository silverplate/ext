<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\String;

class Email extends Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == static::SUCCESS &&
            $_value != '' &&
            !String::isEmail($_value)
        ) {
            return static::ERROR_SPELLING;
        }

        return $status;
    }
}
