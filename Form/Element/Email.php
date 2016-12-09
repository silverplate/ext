<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Str as Str;

class Email extends Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == static::SUCCESS &&
            $_value != '' &&
            !Str::isEmail($_value)
        ) {
            return static::ERROR_SPELLING;
        }

        return $status;
    }
}
