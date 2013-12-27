<?php

namespace Ext\Xml;

class Dom
{
    /**
     * @param \DOMElement $_parent
     * @param string $_name
     * @return \DOMElement
     */
    public static function getChildByName($_parent, $_name)
    {
        return $_parent->getElementsByTagName($_name)->item(0);
    }

    /**
     * @param \DOMElement $_node
     * @return \DOMElement
     */
    public static function remove($_node)
    {
        return $_node->parentNode->removeChild($_node);
    }

    /**
     * @param string $_xml
     * @return \DOMDocument
     */
    public static function get($_xml)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadXML($_xml, LIBXML_DTDLOAD + LIBXML_COMPACT + LIBXML_NOENT);

        return $dom;
    }

    /**
     * @param string $_path
     * @return \DOMDocument
     */
    public static function load($_path)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->load($_path, LIBXML_DTDLOAD + LIBXML_COMPACT + LIBXML_NOENT);

        return $dom;
    }

    /**
     * @param \DOMDocument|\DOMElement $_source
     * @param bool $_doFormat
     * @return string
     * @throws \Exception
     */
    public static function getXml($_source, $_doFormat = false)
    {
        $source = $_source;
        $source->formatOutput = $_doFormat;

        if ($source instanceof \DOMDocument) {
            return $source->saveXML();

        } else if ($source instanceof \DOMElement) {
            return $source->ownerDocument->saveXML($source);
        }

        throw new \Exception(
            'Incompatible source type. \DOMDocument or \DOMElement is expected.'
        );
    }

    /**
     * @param \DOMDocument|\DOMElement $_source
     * @param bool $_doFormat
     * @return string
     * @throws \Exception
     */
    public static function getInnerXml($_source, $_doFormat = false)
    {
        if ($_source instanceof \DOMDocument) {
            return static::getXml($_source->documentElement, $_doFormat);

        } else if ($_source instanceof \DOMElement) {
            $xml = array();

            foreach ($_source->childNodes as $child) {
                $xml[] = static::getXml($child, $_doFormat);
            }

            return implode($_doFormat ? PHP_EOL : '', $xml);
        }

        throw new \Exception(
            'Incompatible source type. \DOMDocument or \DOMElement is expected.'
        );
    }
}
