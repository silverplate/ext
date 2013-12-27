<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Xml;
use \Ext\Date;

class CalendarDatetime extends Element
{
    private $_names = array('date', 'hour', 'minute');

    private function _getPrefixes()
    {
        return array('', $this->getName() . '_', $this->getName() . '-');
    }

    public function getXml()
    {
        $xml = '<hour>';

        for ($i = 0; $i < 24; $i++) {
            $xml .= Xml::node('item', sprintf('%02d', $i));
        }

        $xml .= '</hour><minute>';

        for ($i = 0; $i < 60; $i = $i + 10) {
            $xml .= Xml::node('item', sprintf('%02d', $i));
        }

        $xml .= '</minute>';

        $this->addAdditionalXml($xml);

        return parent::getXml();
    }

    public function computeValue($_data)
    {
        if (isset($_data[$this->getName()])) {
            $date = Date::getDate($_data[$this->getName()]);

            return array('date'   => date('Y-m-d', $date),
                         'hour'   => date('H', $date),
                         'minute' => date('i', $date));

        } else {
            $value = array();

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

        if (
            $this->isRequired() &&
            count($value) != count($this->_names)
        ) {
            return static::ERROR_REQUIRED;

        } else if (
            empty($value['date']) &&
            empty($value['hour']) &&
            empty($value['minute'])
        ) {
            return static::NO_UPDATE;

        } else if (
            (empty($value['date']) || Date::getDate($value['date'])) &&
            (int) $value['hour'] < 24 &&
            (int) $value['minute'] < 60
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

            if (empty($v['date'])) {
                return array($this->getName() => '');

            } else {
                return array($this->getName() => $v['date'] . ' ' .
                                                 $v['hour'] . ':' .
                                                 $v['minute'] . ':00');
            }

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
                $arg = Date::getDate($arg);

                $value = array('date'   => date('Y-m-d', $arg),
                               'hour'   => date('H', $arg),

                               // Приведение минут к ровному счету
                               // (см. возможные значения в getXml в additional/minute/item).
                               'minute' => floor(date('i', $arg) / 10) * 10);
            }

            parent::setValue($value);

        } else {
            $args = func_get_args();
            call_user_func_array('parent::setValue', $args);
        }
    }
}
