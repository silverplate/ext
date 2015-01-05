<?php

namespace Ext;

use \Ext\Form\Element;
use \Ext\Form\Button;
use \Ext\Form\Group;
use \Ext\Xml\Dom;

class Form extends \StdClass
{
    const NO_UPDATE   = 'no-update';
    const SUCCESS     = 'success';
    const ERROR       = 'error';
    const WAS_SUCCESS = 'was-success';
    const COOKIE_NAME = 'form-was-success';

    /** @var string */
    protected $_updateStatus;

    /** @var string */
    protected $_resultMessage;

    /** @var Group[] */
    protected $_groups = array();

    /** @var Element[] */
    protected $_elements = array();

    /** @var Button[] */
    protected $_buttons = array();

    public function __construct()
    {
        $this->setUpdateStatus(static::NO_UPDATE);
    }

    public function __isset($_name)
    {
        return property_exists($this, $_name) || $this->getElement($_name);
    }

    public function __get($_name)
    {
        $ele = $this->getElement($_name);
        if ($ele) return $ele;
        else      throw new \Exception("There is no form element `$_name`.");
    }

    public function __set($_name, $_value)
    {
        $ele = $this->getElement($_name);
        if ($ele) return $ele->setValue($_value);
        else      throw new \Exception("There is no form element `$_name`.");
    }

    public function computeInnerName($_name)
    {
        if (isset($this->_elements[$_name])) {
            return $_name;

        } else {
            $name = String::underline($_name);
            if (isset($this->_elements[$name])) {
                return $name;
            }

            $name = String::dash($_name);
            if (isset($this->_elements[$name])) {
                return $name;
            }
        }

        return false;
    }

    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * @param string $_name
     * @return Group|bool
     */
    public function getGroup($_name)
    {
        return empty($this->_groups[$_name]) ? false : $this->_groups[$_name];
    }

    public function getElements()
    {
        return $this->_elements;
    }

    public function hasElement($_name)
    {
        return (bool) $this->computeInnerName($_name);
    }

    /**
     * @param string $_name
     * @return Element|bool
     */
    public function getElement($_name)
    {
        $name = $this->computeInnerName($_name);
        if ($name) return $this->_elements[$name];
        else       return false;
    }

    /**
     * @param string $_name
     * @param string $_title
     * @return Group
     */
    public function createGroup($_name = null, $_title = null)
    {
        $this->_groups[$_name] = new Group($_name, $_title);

        if (isset($_COOKIE['form-group']) && $_COOKIE['form-group'] == $_name) {
            $this->_groups[$_name]->isSelected(true);
        }

        return $this->_groups[$_name];
    }

    public function computeElementClassName($_type)
    {
        $master = '\\Ext\\Form\\Element';
        $class = $master . '\\' . String::upperCase($_type);
        if (!class_exists($class)) $class = $master;

        return $class;
    }

    /**
     * @param string $_name
     * @param string $_type
     * @param string $_label
     * @param bool $_isRequired
     * @return Element
     */
    public function createElement($_name,
                                  $_type,
                                  $_label = null,
                                  $_isRequired = false)
    {
        $class = $this->computeElementClassName($_type);

        $this->_elements[$_name] = new $class(
            $_name,
            $_type,
            $_label,
            $_isRequired
        );

        return $this->_elements[$_name];
    }

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

    public function delete($_name)
    {
        $name = $this->computeInnerName($_name);

        if ($name) {
            unset($this->_elements[$name]);

            foreach ($this->_groups as $group) {
                $group->deleteElement($name);
            }

        } else {
            throw new \Exception("There is no form element `$_name`.");
        }
    }

    public function deleteGroup($_name)
    {
        foreach ($this->getGroup($_name)->getElements() as $ele) {
            $this->delete($ele->getName());
        }

        unset($this->_groups[$_name]);
    }

    public function rename($_from, $_to)
    {
        $this->$_from->setName($_to);
        $elements = array();

        foreach (array_keys($this->_elements) as $name) {
            $elements[$this->$name->getName()] = $this->$name;
        }

        $this->_elements = $elements;
    }

    public function createButton($_label, $_name = null, $_type = null)
    {
        $name = empty($_name) ? 'submit' : $_name;
        $this->_buttons[$name] = new Button($name, $_label, $_type);
        return $this->_buttons[$name];
    }

    public function getButtons()
    {
        return $this->_buttons;
    }

    /**
     * @param string $_filePath
     * @return static
     */
    public static function load($_filePath)
    {
        return static::loadXml(file_get_contents($_filePath));
    }

    /**
     * @param string $_xml
     * @return self
     */
    public static function loadXml($_xml)
    {
        $class = get_called_class();
        /** @var self $form */
        $form = new $class;
        $xpath = new \DOMXPath(Dom::get($_xml));
        $groups = $xpath->query('group');

        if ($groups->length > 0) {
            /** @var \DOMElement $group */
            foreach ($groups as $group) {
                $name = $group->getAttribute('name');
                $title = Dom::getChildByName($group, 'title');
                $form->createGroup($name, $title ? $title->nodeValue : null);

                foreach ($xpath->query('element', $group) as $element)
                    $form->createElementByDom($element, $name);
            }

        } else {
            foreach ($xpath->query('element') as $item)
                $form->createElementByDom($item);
        }

        foreach ($xpath->query('button') as $item)
            $form->createButtonByDom($item);

        return $form;
    }

    /**
     * @param \DOMElement $_button
     */
    public function createButtonByDom($_button)
    {
        $this->createButton(
            Dom::getChildByName($_button, 'label')->nodeValue,
            $_button->getAttribute('name'),
            $_button->getAttribute('type')
        );
    }

    /**
     * @param \DOMElement $_element
     * @param string $_groupName
     * @throws \Exception
     */
    public function createElementByDom($_element, $_groupName = null)
    {
        $labelEle = Dom::getChildByName($_element, 'label');
        $label = $labelEle && $labelEle->nodeValue
               ? $labelEle->nodeValue
               : null;

        $element = $this->createElement(
            $_element->getAttribute('name'),
            $_element->getAttribute('type'),
            $label,
            $_element->hasAttribute('is-required')
        );

        if (!$element) {
            throw new \Exception('Unable to create form element.');
        }

        if ($_groupName) {
            $this->_groups[$_groupName]->addElement($element);
        }

        foreach (
            array('description', 'label-description', 'input-description') as
            $item
        ) {
            $descriptionEle = Dom::getChildByName($_element, $item);
            if ($descriptionEle) {
                call_user_func_array(
                    array($element, 'set' . String::upperCase($item)),
                    array($descriptionEle->nodeValue)
                );
            }
        }

        $optionsEle = Dom::getChildByName($_element, 'options');
        if ($optionsEle) {
            $optionGroups = $optionsEle->getElementsByTagName('group');

            if ($optionGroups->length > 0) {
                /** @var \DOMElement $optionGroupEle */
                foreach ($optionGroups as $optionGroupEle) {
                    $optionGroupTitleEle = Dom::getChildByName(
                        $optionGroupEle,
                        'title'
                    );

                    if ($optionGroupTitleEle) {
                        $optionGroup = $element->addOptionGroup(
                            $optionGroupTitleEle->nodeValue
                        );

                        /** @var \DOMElement $option */
                        foreach (
                            $optionGroupEle->getElementsByTagName('item') as
                            $option
                        ) {
                            $optionLabel = $option->nodeValue;
                            $optionValue = $option->hasAttribute('value')
                                         ? $option->getAttribute('value')
                                         : $optionLabel;

                            $element->addOption(
                                $optionValue,
                                $optionLabel,
                                $optionGroup
                            );
                        }
                    }
                }

            } else {
                /** @var \DOMElement $option */
                foreach ($optionsEle->getElementsByTagName('item') as $option) {
                    $optionLabel = $option->nodeValue;
                    $optionValue = $option->hasAttribute('value')
                                 ? $option->getAttribute('value')
                                 : $optionLabel;

                    $element->addOption($optionValue, $optionLabel);
                }
            }
        }

        $valueEle = Dom::getChildByName($_element, 'value');
        if ($valueEle && $valueEle->childNodes->length > 0) {
            if (
                $valueEle->firstChild->nodeType == XML_ELEMENT_NODE ||
                $valueEle->childNodes->length > 1
            ) {
                foreach ($valueEle->childNodes as $value) {
                    if ($value->nodeType == XML_ELEMENT_NODE) {
                        $element->setValue($value->nodeName, $value->nodeValue);
                    }
                }

            } else if ($valueEle->firstChild->nodeValue) {
                $element->setValue($valueEle->firstChild->nodeValue);
            }
        }

        $additional = Dom::getChildByName($_element, 'additional');
        if ($additional) {
            foreach ($additional->attributes as $item) {
                $element->setAdditionalXmlAttribute($item->name, $item->value);
            }

            foreach ($additional->childNodes as $item) {
                if ($item->nodeType == XML_ELEMENT_NODE) {
                    $element->addAdditionalXml(
                        $_element->ownerDocument->saveXml($item)
                    );
                }
            }
        }
    }

    public function getXml($_xml = null, $_attrs = null, $_nodeName = null)
    {
        $attrs = empty($_attrs) ? array() : $_attrs;
        if ($this->getUpdateStatus()) {
            $attrs['status'] = $this->getUpdateStatus();
        }

        $xml = Xml::notEmptyCdata('result-message', $this->_resultMessage);
        if ($_xml) $xml .= $_xml;

        $elements = count($this->_groups) > 0
                  ? $this->_groups
                  : $this->_elements;

        foreach ($elements as $item) {
            $xml .= $item->getXml();
        }

        foreach ($this->_buttons as $item) {
            $xml .= $item->getXml();
        }

        return Xml::node(empty($_nodeName) ? 'form' : $_nodeName, $xml, $attrs);
    }

    public function isSubmited($_button = null)
    {
        if (is_null($_button)) {
            foreach ($this->_buttons as $button) {
                if ($button->isSubmited()) {
                    return true;
                }
            }

        } else if (
            isset($this->_buttons[$_button]) &&
            $this->_buttons[$_button]->isSubmited()
        ) {
            return true;
        }

        return false;
    }

    public function isSuccess()
    {
        return $this->getUpdateStatus() == static::SUCCESS;
    }

    public function isError()
    {
        return $this->getUpdateStatus() == static::ERROR;
    }

    public function run()
    {
        if ($this->isSubmited()) {
            $this->setUpdateStatus(static::SUCCESS);

            foreach ($this->_elements as $item) {
                switch ($item->getType()) {
                    case 'file':
                    case 'image':
                    case 'files':
                        $item->computeUpdateStatus($_FILES);
                        break;

                    default:
                        $item->computeUpdateStatus($_POST);
                }

                if ($item->isError()) {
                    $this->setUpdateStatus(static::ERROR);
                }
            }

        } else if ($this->applyCookieStatus()) {
            static::clearCookieStatus();

        } else {
            $this->setUpdateStatus(static::NO_UPDATE);
        }
    }

    public function getUpdateStatus()
    {
        return $this->_updateStatus;
    }

    public function setUpdateStatus($_status)
    {
        $this->_updateStatus = $_status;
        return $this;
    }

    public function getResultMessage()
    {
        return $this->_resultMessage;
    }

    public function setResultMessage($_message)
    {
        $this->_resultMessage = $_message;
        return $this;
    }

    public function fill($_values)
    {
        foreach ($this->_elements as $element) {
            $value = $element->computeValue($_values);

            if ($value !== false) {
                $element->setValue($value);
            }
        }
    }

    public function toArray()
    {
        $result = array();

        foreach ($this->_elements as $element) {
            $value = $element->getValues();

            if ($value) {
                $result = array_merge($result, $value);
            }
        }

        return $result;
    }

    public function uploadFiles($_uploadDir,
                                $_fileNameType = 'real',
                                $_fields = null)
    {
        if ($_uploadDir) {
            $uploadDir = rtrim($_uploadDir, '/') . '/';
            $uploaded = array();
            $deleted = array();

            $fields = empty($_fields) ? array() : $_fields;
            if (!is_array($fields)) $fields = array($fields);

            foreach ($this->_elements as $el) {
                if (
                    $el->isSuccess() &&
                    in_array($el->getType(), array('file', 'image')) &&
                    (empty($fields) || in_array($el->getName(), $fields))
                ) {
                    $value      = $el->getValue();
                    $ext        = $el->getName() . '-present';

                    $isUploaded = !empty($value) &&
                                  !empty($value['name']) &&
                                  !empty($value['tmp_name']);
                    $isDelete   = !empty($_POST[$el->getName() . '-delete']);
                    $isExist    = !empty($_POST[$ext]) && is_file($_POST[$ext]);

                    if ($isExist && ($isUploaded || $isDelete)) {
                        File::deleteFile($_POST[$ext]);

                        if ($isDelete) {
                            $deleted[$el->getName()] = $_POST[$ext];
                        }
                    }

                    if ($isUploaded) {
                        switch ($_fileNameType) {
                            case 'field':
                                $fileName = $el->getName();
                                $extension = File::computeExt($value['name']);

                                if ($extension) {
                                    $fileName .= '.' . strtolower($extension);
                                }

                                break;

                            case 'real':
                            default:
                                $fileName = File::normalizeName($value['name']);
                                break;
                        }

                        File::createDir($uploadDir);
                        move_uploaded_file(
                            $value['tmp_name'],
                            $uploadDir . $fileName
                        );

                        File::chmod($uploadDir . $fileName, 0777);
                        $uploaded[$el->getName()] = $uploadDir . $fileName;
                    }

                    if (File::isDirEmpty($uploadDir)) {
                        rmdir($uploadDir);
                    }
                }
            }

            return count($uploaded) > 0 || count($deleted) > 0
                 ? array('uploaded' => $uploaded, 'deleted' => $deleted)
                 : false;
        }

        return false;
    }

    public static function saveCookieStatus()
    {
        setcookie(static::COOKIE_NAME, 1, 0, '/');
    }

    public static function clearCookieStatus()
    {
        setcookie(static::COOKIE_NAME, '', time() - 3600, '/');
    }

    public static function wasCookieStatus()
    {
        return !empty($_COOKIE[static::COOKIE_NAME]);
    }

    public function applyCookieStatus($_message = null)
    {
        if (static::wasCookieStatus()) {
            $this->setUpdateStatus(static::WAS_SUCCESS);

            if (!empty($_message)) {
                $this->setResultMessage($_message);
            }

            return true;
        }

        return false;
    }

    public static function getCookieStatusXml($_message = null)
    {
        if (static::wasCookieStatus()) {
            return Xml::node(
                'form-status',
                Xml::notEmptyCdata('result-message', $_message),
                array('status' => static::WAS_SUCCESS)
            );
        }

        return false;
    }
}
