<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;
use \Ext\Xml;
use \Ext\Date;

class TimePeriod extends Element
{
    protected $_names = array('hour', 'minute');

    protected function _getPrefixes()
    {
        return array('', $this->getName() . '_', $this->getName() . '-');
    }

    protected function _getTypes()
    {
        return array('from', 'till');
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
                $value['from_hour']   = date('H', $from);
                $value['from_minute'] = date('i', $from);
            }

            if ($till) {
                $value['till_hour']   = date('H', $till);
                $value['till_minute'] = date('i', $till);
            }

            return $value;

        } else {
            $value = array();

            foreach ($this->_getPrefixes() as $prefix) {
                foreach ($this->_names as $name) {
                    foreach ($this->_getTypes() as $type) {
                        foreach (array('_', '-') as $item) {
                            $key = $prefix . $type . $item . $name;

                            if (isset($_data[$key])) {
                                $value[$type . '_' . $name] = $_data[$key];
                            }
                        }
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

        foreach ($this->_getPrefixes() as $prefix)
            foreach ($this->_names as $name)
                foreach ($this->_getTypes() as $type)
                    foreach (array('_', '-') as $item) {
                        $key = $prefix . $type . $item . $name;

                        if (!empty($_value[$key])) {
                            $value[$type . '_' . $name] = $_value[$key];
                        }
                    }

        if (
            $this->isRequired() &&
            count($value) * 2 != count($this->_names)
        ) {
            return static::ERROR_REQUIRED;

        } else if (empty($value)) {
            return static::NO_UPDATE;

        } else if (
            Date::checkTime($value['from_hour'], $value['from_minute']) &&
            Date::checkTime($value['till_hour'], $value['till_minute'])
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

            if (!empty($v['from_hour'])) {
                $values[$this->getName() . '_from'] =
                    $v['from_hour'] . ':' . $v['from_minute'];
            }

            if (!empty($v['till_hour'])) {
                $values[$this->getName() . '_till'] =
                    $v['till_hour'] . ':' . $v['till_minute'];
            }

            return $values;

        } else {
            return false;
        }
    }
}
