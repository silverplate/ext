<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Collection extends Element
{
    public function computeValue($_data)
    {
        $value = array();

        if (isset($_data[$this->getName()])) {
            $value = $_data[$this->getName()];

            if (!is_array($value)) {
                $value = array($value);
            }
        }

        return $value;
    }

    public function checkValue($_value = null)
    {
        if ($this->isRequired() && (empty($_value) || !is_array($_value))) {
            return static::ERROR_REQUIRED;

        } else if (is_null($_value)) {
            return static::NO_UPDATE;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            return array($this->getName() => implode(',', $this->getValue()));

        } else {
            return false;
        }
    }
}
