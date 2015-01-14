<?php

namespace Ext\Form;

trait OrderElementTrait
{
    public function orderElementAfter($_name, $_after)
    {
        $move = $this->_elements[$_name];
        $tmp = [];

        foreach ($this->_elements as $name => $element) {
            if ($name == $_name) continue;

            $tmp[$name] = $element;

            if ($name == $_after)
                $tmp[$_name] = $move;
        }

        $this->_elements = $tmp;
    }
}
