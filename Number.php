<?php

namespace Ext;

class Number
{
    public static function number($_number)
    {
        $number = str_replace(' ', '', $_number);

        if (stripos($number, 'e') !== false) {
            $number = round((float) str_replace(',', '.', $number), 2);

        } else {
            $number = (float) preg_replace(
                '/^([0-9\-]+)[.,]([0-9]{1,2}).*$/',
                '\1.\2',
                $number
            );
        }

        if ((float) $number == (int) $number) {
            $number = (int) $number;
        }

        return $number;
    }

    public static function isInteger($_num)
    {
        return $_num == '0' || (bool) preg_match('/^-?[1-9][0-9]*$/', $_num);
    }

    public static function isFloat($_num)
    {
        return $_num == '0' || (bool) preg_match('/^-?[0-9]+\.[0-9]+$/', $_num);
    }

    public static function isNumber($_number)
    {
        return static::isInteger($_number) || static::isFloat($_number);
    }

    public static function format($_number, $_decimals = null)
    {
        return static::isInteger($_number)
             ? number_format($_number, null, null, ' ')
             : static::formatDecimal($_number, $_decimals);
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
