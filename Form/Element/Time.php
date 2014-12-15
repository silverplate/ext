<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Date;
use \Ext\Xml;

class Time extends Element
{
    private $_names = array('hour', 'minute');

    public function getXml()
    {
        $xml = '<hour>';

        for ($i = 0; $i < 24; $i++)
            $xml .= Xml::node('item', sprintf('%02d', $i));

        $xml .= '</hour><minute>';

        for ($i = 0; $i < 60; $i = $i + 10)
            $xml .= Xml::node('item', sprintf('%02d', $i));

        $xml .= '</minute>';

        $this->addAdditionalXml($xml);

        return parent::getXml();
    }

    private function _getPrefixes()
    {
        return array('', $this->getName() . '_', $this->getName() . '-');
    }

    public function computeValue($_data)
    {
        $value = array();

        if (isset($_data[$this->getName()])) {
            $date = Date::getDate($_data[$this->getName()]);

            return ['hour' => date('H', $date), 'minute' => date('i', $date)];

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

        } else if (Date::checkTime($value['hour'], $value['minute'])) {
            return static::SUCCESS;

        } else {
            return static::ERROR_SPELLING;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            $v = $this->getValue();
            return [$this->getName() => $v['hour'] . ':' . $v['minute'] . ':00'];

        } else {
            return false;
        }
    }
}
