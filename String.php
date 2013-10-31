<?php

namespace Ext;

class String
{
    public static function _split($_string)
    {
        $res = array('');
        $lc = strtolower($_string);
        $uc = strtoupper($_string);
        $sep = array('_', '-', ':', '\\');

        for ($j = 0, $len = strlen($_string), $i = 0; $i < $len; $i++) {
            $symb = $_string{$i};

            if (
                !empty($res[$j]) &&
                (in_array($symb, $sep) || ($symb == $uc{$i} && !is_numeric($symb)))
            ) {
                $res[++$j] = '';
            }

            if (!in_array($symb, $sep)) {
                $res[$j] .= $lc{$i};
            }
        }

        return $res;
    }

    public static function upperCase($_str, $_isLcFirst = false)
    {
        $res = str_replace(' ', '', ucwords(implode(' ', self::_split($_str))));
        return $_isLcFirst ? lcfirst($res) : $res;
    }

    public static function underline($_string)
    {
        return implode('_', self::_split($_string));
    }

    public static function dash($_string)
    {
        return implode('-', self::_split($_string));
    }

    /**
     * @param string $_string
     * @return string
     */
    public static function translit($_string)
    {
        $result = '';
        $rus = array(
            'а' => 'a',  'б' => 'b',   'в' => 'v', 'г' => 'g',  'д' => 'd',
            'е' => 'e',  'ё' => 'e',   'ж' => 'j', 'з' => 'z',  'и' => 'i',
            'й' => 'y',  'к' => 'k',   'л' => 'l', 'м' => 'm',  'н' => 'n',
            'о' => 'o',  'п' => 'p',   'р' => 'r', 'с' => 's',  'т' => 't',
            'у' => 'u',  'ф' => 'f',   'х' => 'h', 'ц' => 'c',  'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',  'ы' => 'i',  'ь' => '',
            'э' => 'e',  'ю' => 'u',   'я' => 'ya'
        );

        for ($i = 0; $i < mb_strlen($_string); $i++) {
            $char = self::getPart($_string, $i, 1);

            if (isset($rus[$char])) {
                $result .= $rus[$char];

            } else if (isset($rus[mb_strtolower($char)])) {
                $result .= ucfirst($rus[mb_strtolower($char)]);

            } else {
                $result .= $char;
            }
        }

        return trim($result);
    }

    /**
     * @param string $_email
     * @return boolean
     */
    public static function isEmail($_email)
    {
        return (boolean) preg_match(
            '/^[0-9a-zA-Z_.-]+@([0-9a-zA-Z][0-9a-zA-Z-]*\.)+[a-zA-Z]{2,4}$/',
//             '/^[0-9a-zA-Z_][0-9a-zA-Z_.-]*[0-9a-zA-Z_-]@([0-9a-zA-Z][0-9a-zA-Z-]*\.)+[a-zA-Z]{2,4}$/',
            $_email
        );
    }

    /**
     * @param string $_value
     * @return array
     */
    public static function split($_value)
    {
        $result = array();

        if ($_value) {
            $list = str_replace(array("\r\n", "\n", ','), ';', $_value);
            $list = preg_replace("/;+/", ';', $list);

            foreach (explode(';', $list) as $item) {
                $item = trim($item);
                if ($item != '') $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param string $_data
     * @param boolean $_isNamed
     * @return array[array]
     */
    public static function toArray($_data, $_isNamed = false)
    {
        $result = array();

        if ($_data) {
            $data = preg_replace('/^#.*$/m', '', $_data);
            $data = preg_split("/\r?\n/", $data, null, PREG_SPLIT_NO_EMPTY);

            if (count($data) > 0) {
                if (!$_isNamed) {
                    foreach ($data as $row) {
                        $result[] = explode("\t", $row);
                    }

                } else if (count($data) > 1) {
                    $names = explode("\t", $data[0]);
                    $data = array_slice($data, 1);

                    foreach ($data as $row) {
                        $item = array();

                        foreach (explode("\t", $row) as $i => $col) {
                            if (empty($names[$i])) $item[] = $col;
                            else                   $item[$names[$i]] = $col;
                        }

                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }

    public static function toUpper($_string)
    {
        return mb_strtoupper($_string);
    }

    public static function toUpperFirst($_string)
    {
        if ($_string) {
            $result = self::toUpper(self::getPart($_string, 0, 1));

            if (self::getLength($_string) > 1) {
                $result .= self::getPart($_string, 1);
            }

            return $result;
        }

        return $_string;
    }

    public static function toLower($_string)
    {
        return mb_strtolower($_string);
    }

    public static function getPart($_string, $_start, $_length = null)
    {
        return $_length
            ? mb_substr($_string, $_start, $_length)
            : mb_substr($_string, $_start);
    }

    public static function getRandom($_length = 8)
    {
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $result = '';

        for ($i = 0; $i < $_length; $i++) {
            if (0 == rand(0, 3)) {
                $symbol = $letters{rand(0, strlen($letters) - 1)};

                if (0 == rand(0, 3)) {
                    $symbol = strtoupper($symbol);
                }

            } else {
                $symbol = $numbers{rand(0, strlen($numbers) - 1)};
            }

            $result .= $symbol;
        }

        return $result;
    }

    /**
     * Обычно используется как пароль.
     *
     * @param integer $_length Минимум 4.
     * @return string
     */
    public static function getRandomReadable($_length)
    {
        $consonant = 'bcdfghjklmnpqrstvwxz';
        $vowel = 'aeiouy';
        $result = '';
        $length = $_length < 4 ? 4 : $_length;
        $pairs = floor($length / 2) - 1;

        for ($i = 0; $i < $pairs; $i++) {
            $result .= $consonant[rand(0, strlen($consonant) - 1)];
            $result .= $vowel[rand(0, strlen($vowel) - 1)];
        }

        if ($length % 2 != 0) {
            $result .= $consonant[rand(0, strlen($consonant) - 1)];
        }

        if ($length > 2) {
            $result .= rand(0, 9) . rand(0, 9);
        }

        return $result;
    }

    /**
     * Как self::getRandomReadable, но ставит последнее число первым символом в строке.
     *
     * @param integer $_length
     * @return string
     * @see self::getRandomReadable
     */
    public static function getRandomReadableAlt($_length = null)
    {
        $str = self::getRandomReadable($_length);

        return substr($str, strlen($str) - 1, 1) .
        substr($str, 0, strlen($str) - 1);
    }

    public static function cut($_string, $_length, $_isHtml = true)
    {
        $result = '';

        if (mb_strlen($_string) > $_length) {
            $l = 0;

            foreach (explode(' ', $_string) as $item) {
                $l += mb_strlen($item);
                if ($l >= $_length) break;
                else $result .= ($result == '' ? '' : ' ') . $item;
            }

            $result  = rtrim($result, '.,');
            $result .= $_isHtml ? '&hellip;' : '...';

        } else {
            $result = $_string;
        }

        return $result;
    }

    public static function hardWrap($_string, $_chunkLength)
    {
        if (strlen($_string) <= $_chunkLength) {
            return $_string;
        }

        $result = '';
        $count = ceil(strlen($_string) / $_chunkLength);

        for ($i = 0; $i < $count; $i++) {
            $result .= substr($_string, $i * $_chunkLength, $_chunkLength);

            if ($i != $count - 1) {
                $result .= "\n";
            }
        }

        return $result;
    }

    public static function wordWrap($_string, $_width, $_break = null, $_cut = null)
    {
        return iconv('cp1251', 'utf-8', wordwrap(
            iconv('utf-8', 'cp1251', $_string),
            $_width,
            $_break,
            $_cut
        ));
    }

    /**
     * Правильная форма cуществительного рядом с числом (счетная форма).
     *
     * @param integer $_number Число
     * @param string $_case1 Единственное число именительный падеж
     * @param string $_case2 Единственное число родительный падеж
     * @param string $_case3 Множественное число родительный падеж
     * @return string
     */
    public static function getCase($_number, $_case1, $_case2, $_case3)
    {
        $number = abs($_number);
        if ($number > 20) $number %= 10;

        if ($number == 1)                    return $_case1;
        else if ($number > 1 && $number < 5) return $_case2;
        else                                 return $_case3;
    }

    public static function getLength($_string)
    {
        return mb_strlen($_string);
    }

// Пока реализация XML не требуется.
//    /**
//     * Символы &mdash;, &times; и другие заменяются на текстовый вариант.
//     *
//     * @param string $_content
//     * @return string
//     */
//    public static function replaceEntities($_content)
//    {
//        $matches = array();
//        $content = $_content;
//        preg_match_all('/&[0-9a-zA-Z]+;/', $content, $matches);
//
//        if ($matches) {
//            $dom = new DOMDocument('1.0', 'utf-8');
//            $dom->loadXML(
//                Ext_Xml::getDocument('<e>' . implode('</e><e>', $matches[0]) . '</e>'),
//                LIBXML_DTDLOAD + LIBXML_COMPACT + LIBXML_NOENT + LIBXML_NOERROR
//            );
//
//            $entities = $dom->getElementsByTagName('e');
//
//            for ($i = 0; $i < $entities->length; $i++) {
//                $value = $entities->item($i)->nodeValue;
//
//                if ($value) {
//                    $content = str_replace($matches[0][$i], $value, $content);
//                }
//            }
//        }
//
//        return $content;
//    }
}
