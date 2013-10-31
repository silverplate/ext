<?php

namespace Ext;

class Number
{
    public static function number($_number)
    {
        $number = str_replace(' ', '', $_number);

        $number = stripos($number, 'e') !== false
                ? round((float) str_replace(',', '.', $number), 2)
                : (float) preg_replace('/^([0-9\-]+)[.,]([0-9]{1,2}).*$/', '\1.\2', $number);

        if ((float) $number == (int) $number) {
            $number = (int) $number;
        }

        return $number;
    }

    public static function isInteger($_number)
    {
        return $_number == '0' || (boolean) preg_match('/^-?[1-9][0-9]*$/', $_number);
    }

    public static function isFloat($_number)
    {
        return $_number == '0' || (boolean) preg_match('/^-?[0-9]+\.[0-9]+$/', $_number);
    }

    public static function isNumber($_number)
    {
        return self::isInteger($_number) || self::isFloat($_number);
    }

    public static function format($_number, $_decimals = null)
    {
        return self::isInteger($_number)
             ? number_format($_number, null, null, ' ')
             : self::formatDecimal($_number, $_decimals);
    }

    public static function formatDecimal($_number, $_decimals = null)
    {
        return number_format(
            $_number,
            is_null($_decimals) ? 2 : $_decimals, ',',
            ' '
        );
    }
}
