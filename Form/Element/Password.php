<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Password extends Element
{
    public function computeValue($_data)
    {
        $value = array();

        if (
            array_key_exists($this->_name, $_data) &&
            $_data[$this->_name] != ''
        ) {
            $value[$this->_name] = $_data[$this->_name];

            if (array_key_exists($this->_name . '_check', $_data)) {
                $value[$this->_name . '_check'] =
                    $_data[$this->_name . '_check'];
            }
        }

        if (count($value) > 0) {
            return $value;
        }

        return false;
    }

    public function checkValue($_value = null)
    {
        if (
            $this->isRequired() && (
                empty($_value) ||
                !array_key_exists($this->_name, $_value) ||
                $_value[$this->_name] == '' ||
                !array_key_exists($this->_name . '_check', $_value) ||
                $_value[$this->_name . '_check'] == ''
            )
        ) {
            return static::ERROR_REQUIRED;

        } else if (
            empty($_value) || (
                !array_key_exists($this->_name, $_value) &&
                !array_key_exists($this->_name . '_check', $_value)
            )
        ) {
            return static::NO_UPDATE;

        } else if (
            !empty($_value) &&
            $_value[$this->_name] != $_value[$this->_name . '_check']
        ) {
            return static::ERROR_SPELLING;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        return $this->getUpdateStatus() == static::SUCCESS
             ? array($this->_name => $this->getValue($this->_name))
             : false;
    }
}
