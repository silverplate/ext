<?php

namespace Ext;

class Lib
{
    public static function d()
    {
        $args = func_get_args();
        $count = count($args);

        if ($count == 1) {
            self::debug($args[0]);

        } else {
            foreach ($args as $i => $var) {
                if ($i != 0) echo PHP_EOL;
                echo $i + 1 . ':';
                echo PHP_EOL;

                self::debug($var);
            }
        }

        die();
    }

    public static function debug($_var)
    {
        if (PHP_SAPI == 'cli') {
//             print_r($_var);
            var_dump($_var);
            echo PHP_EOL;

        } else {
//            echo '<pre>';
//            if (is_string($_var)) echo htmlspecialchars($_var);
//            else                  print_r($_var);
//            echo '</pre>';

            if (is_string($_var)) echo htmlspecialchars($_var);
            else var_dump($_var);
        }
    }
}
