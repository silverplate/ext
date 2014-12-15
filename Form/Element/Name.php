<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Name extends Element
{
    public function computeValue($_data)
    {
        $value = array();
        $prefixes = array('', $this->getName() . '_');

        foreach ($prefixes as $prefix) {
            foreach (
                ['last_name', 'first_name', 'middle_name', 'patronymic_name'] as
                $name
            ) {
                if (isset($_data[$prefix . $name])) {
                    $value[$name] = $_data[$prefix . $name];
                }
            }

            if (count($value) > 0) {
                return $value;
            }
        }

        return false;
    }

    public function checkValue($_value = null)
    {
        if (
            $this->isRequired() &&
            (empty($_value['first_name']) || empty($_value['last_name']))
        ) {
            return static::ERROR_REQUIRED;

        } else if (
            !isset($_value['first_name']) &&
            !isset($_value['last_name']) &&
            !isset($_value['middle_name']) &&
            !isset($_value['patronymic_name'])
        ) {
            return static::NO_UPDATE;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            return $this->getValue();

        } else {
            return false;
        }
    }
}
