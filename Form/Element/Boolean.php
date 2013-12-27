<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Boolean extends Element
{
    public function computeValue($_data)
    {
        return empty($_data[$this->getName()]) ? 0 : 1;
    }
}
