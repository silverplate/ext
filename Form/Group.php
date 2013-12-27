<?php

namespace Ext\Form;

use \Ext\Xml;

class Group
{
    /** @var string */
    protected $_name;

    /** @var string */
    protected $_title;

    /** @var bool */
    protected $_isSelected = false;

    /** @var Element[] */
    protected $_elements = array();

    /** @var string */
    protected $_additionalXml;

    public function __construct($_name = null, $_title = null)
    {
        if (!is_null($_name)) {
            $this->_name = $_name;
        }

        if (!is_null($_title)) {
            $this->_title = $_title;
        }
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function isSelected($_isSelected = null)
    {
        if (is_null($_isSelected)) {
            return $this->_isSelected;
        } else {
            $this->_isSelected = (boolean) $_isSelected;
            return $this;
        }
    }

    /**
     * @return Element[]
     */
    public function getElements()
    {
        return $this->_elements;
    }

    /**
     * @param Element $_element
     */
    public function addElement(Element $_element)
    {
        $this->_elements[$_element->getName()] = $_element;
    }

    public function deleteElement($_name)
    {
        unset($this->_elements[$_name]);
    }

    public function setAdditionalXml($_value)
    {
        $this->_additionalXml = $_value;
    }

    public function addAdditionalXml($_value)
    {
        $this->_additionalXml .= $_value;
    }

    public function getAdditionalXml()
    {
        return $this->_additionalXml;
    }

    public function getXml()
    {
        $attrs = array();

        if ($this->getName()) {
            $attrs['name'] = $this->getName();
        }

        if ($this->isSelected()) {
            $attrs['is-selected'] = 'true';
        }

        $xml  = Xml::notEmptyCdata('title', $this->getTitle());
        $xml .= Xml::notEmptyNode('additional', $this->getAdditionalXml());

        foreach ($this->getElements() as $item) {
            $xml .= $item->getXml();
        }

        return Xml::node('group', $xml, $attrs);
    }
}
