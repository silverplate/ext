<?php

namespace Ext;

use \Ext\Xml\Dom;

class Xml
{
    public static function normalize($_name)
    {
        return String::dash($_name);
    }

    public static function node($_name, $_value = null, $_attrs = null)
    {
        $name = static::normalize($_name);
        $xml = '<' . $name;

        if ($_attrs) {
            foreach ($_attrs as $_key => $_attrValue) {
                if ($_attrValue === '') continue;

                $key = static::normalize($_key);
                $attrValue = str_replace('&', '&amp;', $_attrValue);

                if (strpos($key, 'is-') !== 0) $xml .= " $key=\"$attrValue\"";
                else if ($attrValue)           $xml .= " $key=\"true\"";
            }
        }

        if (empty($_value))         $value = '';
        else if (is_array($_value)) $value = implode($_value);
        else                        $value = $_value;

        if ($value) {
            $value = static::removeControlCharacters($value);
        }

        return $xml . (empty($value) ? ' />' : ">$value</$name>");
    }

    public static function notEmptyNode($_name, $_value = null, $_attrs = null)
    {
        return empty($_value) && empty($_attrs)
             ? ''
             : static::node($_name, $_value, $_attrs);
    }

    public static function encodeCdata($_content)
    {
        return str_replace(
            array('<![CDATA[', ']]>'),
            array('&lt;![CDATA[', ']]&gt;'),
            $_content
        );
    }

    public static function decodeCdata($_content)
    {
        return str_replace(
            array('&lt;![CDATA[', ']]&gt;'),
            array('<![CDATA[', ']]>'),
            $_content
        );
    }

    public static function cdata($_name, $_cdata = null, $_attrs = null)
    {
        $cdata = is_null($_cdata) || $_cdata === ''
               ? null
               : '<![CDATA[' . static::encodeCdata($_cdata) . ']]>';

        return static::node($_name, $cdata, $_attrs);
    }

    public static function notEmptyCdata($_name, $_value = null, $_attrs = null)
    {
        return empty($_value) && empty($_attrs)
             ? ''
             : static::cdata($_name, $_value, $_attrs);
    }

    public static function number($_name, $_number)
    {
        $value = Number::format(abs($_number));
        if ($_number < 0) $value = '&minus;' . $value;

        return static::cdata($_name, $value, array('value' => $_number));
    }

    /**
     * @param string|array $container
     * @param string|array $_xml
     */
    public static function append(&$container, $_xml)
    {
        if (!$_xml) return;

        if (is_array($container)) {
            if (is_array($_xml)) $container = array_merge($container, $_xml);
            else                 $container[] = $_xml;

        } else {
            $container .= is_array($_xml) ? implode($_xml) : $_xml;
        }
    }

    /**
     * Удаление неотображаемых символов (ASCII control characters),
     * используемых в MS Word, которые ломают XML.
     *
     * @link http://www.danshort.com/ASCIImap/indexhex.htm
     * @param string $_src
     * @return string
     */
    public static function removeControlCharacters($_src)
    {
        // Кроме x09, x0A
        return preg_replace("/[\x{7F}\x{00}-\x{08}\x{0B}-\x{1F}]/", '', $_src);
    }

    /**
     * @param string $_xml
     * @return string
     */
    public static function format($_xml)
    {
        return Dom::getInnerXml(Dom::get($_xml)->documentElement, true);
    }

    /**
     * @param bool|string $_dtd
     * @param string $_root
     * @return string
     */
    public static function getHead($_dtd = true, $_root = null)
    {
        $root = empty($_root) ? 'root' : $_root;
        $xml = '<?xml version="1.0" encoding="utf-8"?>';

        if ($_dtd) {
            $dtd = $_dtd === true ? dirname(__FILE__) . '/entities.dtd' : $_dtd;

            if (function_exists('isWindows') && isWindows()) {
                $dtd = 'file:///' . str_replace('\\', '/', $dtd);
            }

            $xml .= PHP_EOL;
            $xml .= "<!DOCTYPE $root SYSTEM \"$dtd\">";
        }

        $xml .= PHP_EOL;
        return $xml;
    }

    /**
     * Передаваемый XML будет помещен внутр корневого элемента $_root
     * с атрибутами $_attrs.
     *
     * @param string $_xml
     * @param string $_attrs
     * @param string $_root
     * @param string|bool $_dtd
     * @return string
     */
    public static function getDocument($_xml,
                                       $_attrs = null,
                                       $_root = null,
                                       $_dtd = true)
    {
        $root = empty($_root) ? 'root' : $_root;

        return static::getHead($_dtd, $root) .
               static::node($root, $_xml, $_attrs);
    }

    /**
     * Передаваемый XML должен содерждать корневой элемент.
     *
     * @param string $_xml
     * @param string $_root
     * @param string|bool $_dtd
     * @return string
     */
    public static function getDocumentForXml($_xml, $_root = null, $_dtd = true)
    {
        return static::getHead($_dtd, empty($_root) ? 'root' : $_root) . $_xml;
    }
}