<?php

namespace Ext\Db\ActiveRecord;

use \Ext\Db;
use \Ext\Number;

class Attribute
{
    protected $_name;
    protected $_type;
    protected $_value;
    protected $_isPrimary;
    protected $_length;

    public function __construct($_name, $_type)
    {
        $this->_name = $_name;
        $this->setType($_type);
    }

    public function setType($_type)
    {
        $this->_type = $_type == 'char' || $_type == 'varchar'
                     ? 'string'
                     : $_type;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setValue($_val)
    {
        switch ($this->_type) {
            case 'integer':
                $this->_value = (string) $_val == '' ? $_val : (int) $_val;
                break;

            case 'float':
                $this->_value = Number::number($_val);
                break;

            case 'boolean':
                $this->_value = $_val ? 1 : 0;
                break;

            default:
                $this->_value = $_val;
                break;
        }
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function getSqlValue()
    {
        return $this->isValue() ? Db::escape($this->getValue()) : 'NULL';
    }

    public function isValue()
    {
        return (string) $this->_value != '';
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isPrimary($_isPrimary = null)
    {
        if ($_isPrimary !== null) {
            $this->_isPrimary = (bool) $_isPrimary;
        }

        return (bool) $this->_isPrimary;
    }

    public function setLength($_length)
    {
        $this->_length = $_length;
    }

    public function getLength()
    {
        return $this->_length;
    }
}
