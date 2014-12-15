<?php

namespace Ext\Form\Element;

class Phone extends \Ext\Form\Element
{
    public function computeValue($_data)
    {
        $value = [];
        $name = $this->getName();

        if (isset($_data[$name])) {
            $value['number'] = $_data[$name];

            if (isset($_data[$name . '_code']))
                $value['code'] = $_data[$name . '_code'];

            return $value;

        } else if (isset($_data[$name . '_number'])) {
            $value['number'] = $_data[$name . '_number'];

            if (isset($_data[$name . '_code']))
                $value['code'] = $_data[$name . '_code'];

            return $value;

        } else {
            return false;
        }
    }

    public function checkValue($_value = null)
    {
        if (
            $this->_isRequired &&
            !(isset($_value['number']) && $_value['number'])
        ) {
            return static::ERROR_REQUIRED;

        } else if (!isset($_value['number'])) {
            return static::NO_UPDATE;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            $name = $this->getName();
            $value = $this->getValue();
            $result = [];

            if (isset($value['number']))
                $result[$name] = $value['number'];

            if (isset($value['code']))
                $result[$name . '_code'] = $value['code'];

            return $result;
        }

        return false;
    }
}
