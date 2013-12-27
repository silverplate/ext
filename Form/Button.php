<?php

namespace Ext\Form;

use \Ext\Xml;

class Button
{
    protected $_name;
    protected $_label;
    protected $_type;

    public function __construct($_name, $_label, $_type = null)
    {
        $this->_name = $_name;
        $this->_label = $_label;
        $this->_type = $_type;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getLabel()
    {
        return $this->_label;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($_type)
    {
        $this->_type = $_type;
    }

    public function isSubmited()
    {
        return isset($_POST[$this->_name]);
    }

    public function getXml()
    {
        $attrs = array('name' => $this->getName());

        if ($this->getType()) {
            $attrs['type'] = $this->getType();
        }

        if ($this->isSubmited()) {
            $attrs['is-submited'] = 'true';
        }

        return Xml::cdata('button', $this->getLabel(), $attrs);
    }
}
