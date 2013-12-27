<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Date AS D;

class Date extends Element
{
    private $_names = array('day', 'month', 'year');

    private function _getPrefixes()
    {
        return array('', $this->getName() . '_', $this->getName() . '-');
    }

    public function computeValue($_data)
    {
        $value = array();

        if (isset($_data[$this->getName()])) {
            if (strpos($_data[$this->getName()], '0000-00-00') === 0) {
                return false;
            }

            $date = D::getDate($_data[$this->getName()]);

            return array('day'   => date('d', $date),
                         'month' => date('m', $date),
                         'year'  => date('Y', $date));

        } else {
            foreach ($this->_getPrefixes() as $prefix) {
                foreach ($this->_names as $name) {
                    if (isset($_data[$prefix . $name])) {
                        $value[$name] = $_data[$prefix . $name];
                    }
                }

                if (count($value) > 0) {
                    return $value;
                }
            }
        }

        return false;
    }

    public function checkValue($_value = null)
    {
        $value = array();

        foreach ($this->_getPrefixes() as $prefix) {
            foreach ($this->_names as $name) {
                if (!empty($_value[$prefix . $name])) {
                    $value[$name] = $_value[$prefix . $name];
                }
            }
        }

        if ($this->isRequired() && count($value) != 3) {
            return static::ERROR_REQUIRED;

        } else if (count($value) == 0) {
            return static::NO_UPDATE;

        } else if (
            D::checkDate($value['month'], $value['day'], $value['year'])
        ) {
            return static::SUCCESS;

        } else {
            return static::ERROR_SPELLING;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            return array($this->getName() =>
                         implode('-', array_reverse($this->getValue())));
        } else {
            return false;
        }
    }

    public function setValue()
    {
        if (func_num_args() == 1) {
            $arg = func_get_arg(0);

            if (is_array($arg)) {
                $value = $arg;

            } else {
                $arg = D::getDate($arg);
                $value = array(
                    'day'   => date('d', $arg),
                    'month' => date('m', $arg),
                    'year'  => date('Y', $arg)
                );
            }

            parent::setValue($value);

        } else {
            $args = func_get_args();
            call_user_func_array('parent::setValue', $args);
        }
    }
}
