<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Date;

class DatePeriod extends Element
{
    protected function _getPrefixes()
    {
        return array('', $this->getName() . '_', $this->getName() . '-');
    }

    protected function _getTypes()
    {
        return array('from', 'till');
    }

    public function computeValue($_data)
    {
        if (
            isset($_data[$this->getName() . '_from']) ||
            isset($_data[$this->getName() . '_till'])
        ) {
            $from = isset($_data[$this->getName() . '_from'])
                  ? Date::getDate($_data[$this->getName() . '_from'])
                  : false;

            $till = isset($_data[$this->getName() . '_till'])
                  ? Date::getDate($_data[$this->getName() . '_till'])
                  : false;

            $value = array();

            if ($from) {
                $value['from'] = date('Y-m-d', $from);
            }

            if ($till) {
                $value['till'] = date('Y-m-d', $till);
            }

            return $value;

        } else {
            $value = array();

            foreach ($this->_getPrefixes() as $prefix) {
                foreach ($this->_getTypes() as $type) {
                    $key = $prefix . $type;

                    if (isset($_data[$key])) {
                        $value[$type] = $_data[$key];
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
            foreach ($this->_getTypes() as $type) {
                $key = $prefix . $type;

                if (!empty($_value[$key])) {
                    $value[$type] = $_value[$key];
                }
            }
        }

        if (
            $this->isRequired() &&
            count($value) != count($this->_getTypes())
        ) {
            return static::ERROR_REQUIRED;

        } else if (empty($value)) {
            return static::NO_UPDATE;

        } else if (
            (empty($value['from']) || Date::getDate($value['from'])) &&
            (empty($value['till']) || Date::getDate($value['till']))
        ) {
            return static::SUCCESS;

        } else {
            return static::ERROR_SPELLING;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            $v = $this->getValue();

            $values = array(
                $this->getName() . '_from' => '',
                $this->getName() . '_till' => ''
            );

            if (!empty($v['from'])) {
                $values[$this->getName() . '_from'] = $v['from'] . ' 00:00:00';
            }

            if (!empty($v['till'])) {
                $values[$this->getName() . '_till'] = $v['till'] . ' 23:59:59';
            }

            return $values;

        } else {
            return false;
        }
    }
}
