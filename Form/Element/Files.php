<?php

namespace Ext\Form\Element;

use \Ext\Form\Element;

class Files extends Element
{
    public function computeValue($_data)
    {
        $value = array();
        $data = is_array($_data) && isset($_data[$this->_name])
              ? $_data[$this->_name]
              : $_data;

        if (
            is_array($data) &&
            !empty($data['name']) &&
            !empty($data['tmp_name'])
        ) {
            for ($i = 0; $i < count($data['name']); $i++) {
                if (
                    !empty($data['name'][$i]) &&
                    !empty($data['tmp_name'][$i])
                ) {
                    $value[] = array(
                        'name' => $data['name'][$i],
                        'tmp_name' => $data['tmp_name'][$i]
                    );
                }
            }
        }

        return $value;
    }

    public function checkValue($_value = null)
    {
        if ($this->isRequired() && empty($_value)) {
            return static::ERROR_REQUIRED;

        } else if (empty($_value)) {
            return static::NO_UPDATE;

        } else {
            return static::SUCCESS;
        }
    }

    public function getValues()
    {
        if ($this->getUpdateStatus() == static::SUCCESS) {
            $value = array();

            foreach ($this->getValue() as $item) {
                $value[] = $item['name'];
            }

            if ($value) {
                return array($this->getName() => implode(', ', $value));
            }
        }

        return false;
    }
}
