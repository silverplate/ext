<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Login extends Element
{
    public function checkValue($_value = null)
    {
        $status = parent::checkValue($_value);

        if (
            $status == static::SUCCESS &&
            $_value != '' &&
            !preg_match('/^[a-zA-Z0-9_.-]+$/', $_value)
        ) {
            return static::ERROR_SPELLING;
        }

        return $status;
    }
}
