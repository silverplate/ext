<?php

namespace Ext;

class Date
{
    const DAY_SEC = 86400;

    protected static $_months = array(
        'ru' => array(
            array('Январь', 'Января', 'Январе'),
            array('Февраль', 'Февраля', 'Феврале'),
            array('Март', 'Марта', 'Марте'),
            array('Апрель', 'Апреля', 'Апреле'),
            array('Май', 'Мая', 'Мае'), array('Июнь', 'Июня', 'Июне'),
            array('Июль', 'Июля', 'Июле'),
            array('Август', 'Августа', 'Августе'),
            array('Сентябрь', 'Сентября', 'Сентябре'),
            array('Октябрь', 'Октября', 'Октябре'),
            array('Ноябрь', 'Ноября', 'Ноябре'),
            array('Декабрь', 'Декабря', 'Декабре')
        ),
        'en' => array(
            'January', 'February', 'March',
            'April', 'May', 'June',
            'July', 'August', 'September',
            'October', 'November', 'December'
        )
    );

    protected static $_daysOfTheWeek = array(
        'ru' => array(
            array('Понедельник'), array('Вторник'), array('Среда'),
            array('Четверг'), array('Пятница'),
            array('Суббота'), array('Воскресенье')
        ),
        'en' => array(
            'Monday', 'Tuesday', 'Wednesday',
            'Thursday', 'Friday',
            'Saturday', 'Sunday'
        )
    );

    public static function getMonths($_lang = 'ru')
    {
        return self::$_months[$_lang];
    }

    public static function getMonth($_number, $_type = null, $_lang = 'ru')
    {
        $number = (int) $_number;
        $names = self::getMonths($_lang);
        $name = $names[$number - 1];

        return is_null($_type) ? $name : $name[$_type - 1];
    }

    public static function guessMonth($_value)
    {
        $value = String::toLower($_value);

        foreach (self::getMonths('ru') as $id => $items) {
            foreach ($items as $item) {
                $item = String::toLower($item);
                if (preg_match('/^' . $value . '/', $item)) {
                    return $id + 1;
                }
            }
        }

        return false;
    }

    public static function getDaysOfTheWeek($_lang = 'ru')
    {
        return self::$_daysOfTheWeek[$_lang];
    }

    public static function getDayOfTheWeek($_num, $_type = null, $_lang = 'ru')
    {
        $number = (int) $_num;
        $names = self::getDaysOfTheWeek($_lang);
        $name = $names[$number - 1];

        return is_null($_type) ? $name : $name[$_type - 1];
    }

    public static function format($_date, $_trimYear = null)
    {
        $year = '';

        if (
            $_trimYear === true ||
            ($_trimYear === null && date('Y') != date('Y', $_date))
        ) {
            $year = date(' Y года', $_date);
        }

        return date('j ', $_date) .
               String::toLower(self::getMonth(date('n', $_date), 2)) .
               $year;
    }

    public static function formatExpanded($_date,
                                          $_isHuman = true,
                                          $_trimYear = null,
                                          $_isTime = null)
    {
        $date = getdate(self::getDate($_date));
        $day = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);

        foreach (array('hours', 'minutes', 'seconds') as $item) {
            if (10 > $date[$item]) {
                $date[$item] = '0' . $date[$item];
            }
        }

        $hm = $date['hours'] . ':' . $date['minutes'];
        $hms = $hm . ':' . $date['seconds'];
        $dmy = $date['mday'] .
               ' ' .
               String::toLower(self::getMonth($date['mon'], 2));

        if (
            (null === $_trimYear && date('Y') != $date['year']) &&
            $_trimYear !== true
        ) {
            $dmy .= ' ' . $date['year'] . ' года';
        }

        if ($_isHuman) {
            switch ($day) {
                case self::yesterday(self::yesterday()):
                    $result = 'Позавчера';
                    break;
                case self::yesterday():
                    $result = 'Вчера';
                    break;
                case self::today():
                    $result = 'Сегодня';
                    break;
                case self::tomorrow():
                    $result = 'Завтра';
                    break;
                case self::tomorrow(self::tomorrow()):
                    $result = 'Послезавтра';
                    break;
                default:
                    $result = $dmy;
            }

        } else {
            $result = $dmy;
        }

        if (
            (null === $_isTime && '00:00:00' != $hms) ||
            true === $_isTime
        ) {
            $result .= ' ' . $hm;
        }

        return $result;
    }

    public static function formatMonth($_date)
    {
        $month = self::getMonth(date('n', $_date), 1);

        if (date('Y') != date('Y', $_date)) {
            $month .= date(', Y', $_date);
        }

        return $month;
    }

    /**
     * @param int $_min
     * @param bool $_isShort
     * @param bool|int $_dayHrs Нужно передать false, чтобы дни не вычислялись.
     * @return string
     */
    public static function formatMinutes($_min, $_isShort = false, $_dayHrs = 8)
    {
        $mins    = abs($_min);
        $sign    = $_min < 0 ? '−' : '';
        $dayMin  = empty($_dayHrs) ? 0 : 60 * $_dayHrs;
        $days    = $dayMin == 0 ? 0 : floor($mins / $dayMin);
        $hours   = floor(($mins - $days * $dayMin) / 60);
        $minutes = round($mins - $days * $dayMin - $hours * 60);

        if (!$days && !$hours && !$minutes) {
            return '0';

        } else if ($_isShort) {
            $result = array();
            $f = '%02d';

            if (!empty($_dayHrs)) {
                $result[]  = sprintf($f, $days);
            }

            $result[] = $hours ? sprintf($f, $hours) : '00';
            $result[] = $minutes ? sprintf($f, $minutes) : '00';

            return $sign . implode(':', $result);

        } else {
            $result = array();

            if ($days)    $result[]  = $days . ' д';
            if ($hours)   $result[]  = $hours . ' ч';
            if ($minutes) $result[]  = $minutes . ' м';

            return $sign . implode(' ', $result);
        }
    }

    public static function htmlFormatMinutes($_min,
                                             $_isShort = false,
                                             $_dayHrs = 8)
    {
        return str_replace(
            '-',
            '&minus;',
            static::formatMinutes($_min, $_isShort, $_dayHrs)
        );
    }

    public static function checkDate($_month, $_day, $_year)
    {
        return checkdate((int) $_month, (int) $_day, (int) $_year);
    }

    public static function checkTime($_hour, $_minute)
    {
        $h = (int) $_hour;
        $m = (int) $_minute;

        return $h >= 0 && $h < 24 && $m >= 0 && $m < 60;
    }

    public static function getXml($_date, $_node = null)
    {
        $attrs = array(
            'unixtimestamp' => $_date,
            'day' => date('d', $_date),
            'day-zeroless' => date('j', $_date),
            'month' => date('m', $_date),
            'year' => date('Y', $_date),
            'date' => date('d.m.Y', $_date),
            'sql-date' => date('Y-m-d', $_date)
        );

        if (
            (int) date('H', $_date) ||
            (int) date('i', $_date) ||
            (int) date('s', $_date)
        ) {
            $attrs['hour'] = date('H', $_date);
            $attrs['minute'] = date('i', $_date);
            $attrs['second'] = date('s', $_date);
            $attrs['time'] = date('H:i', $_date);
            $attrs['sql-date-time'] = date('Y-m-d H:i:s', $_date);
        }

        return Xml::node(
            $_node ? $_node : 'date',
            Xml::cdata('full', self::format($_date)) .
            Xml::cdata('human', self::formatExpanded($_date)),
            $attrs
        );
    }

    /**
     * Распознаются следующие форматы:
     * позавчера, вчера, сегодня, завтра, послезавтра
     * 1 августа, 1 авг, 10/10
     * 1 авг 2009, 1 августа 2009, 1 августа 2009 г., 1 августа 2009 года
     * 01.10.2009, 01.10.09, 01/10/2009, 01/10/09,
     * 10.01.2009, 10.01.09, 10/01/2009, 10/01/09,
     * 2009-10-01
     */
    public static function fromString($_value)
    {
        $value = trim($_value);
        $match = array();

        switch (String::toLower($value)) {
            case 'позавчера':   return self::yesterday(self::yesterday());
            case 'вчера':       return self::yesterday();
            case 'сегодня':     return self::today();
            case 'завтра':      return self::tomorrow();
            case 'послезавтра': return self::tomorrow(self::tomorrow());
        }

        preg_match(
            '/^([0-9]{1,2})[.\/\-]([0-9]{1,2})(?:[.\/\-]([0-9]{2,4}))?$/',
            $value,
            $match
        );

        if ($match) {
            $day = $match[1];
            $month = $match[2];
            $year = empty($match[3]) ? date('Y') : $match[3];

            if (30 >= $year) $year += 2000;
            else if (100 > $year) $year += 1900;

            if (static::checkDate($month, $day, $year)) {
                return mktime(12, 0, 0, $month, $day, $year);

            } else if (static::checkDate($day, $month, $year)) {
                return mktime(12, 0, 0, $day, $month, $year);
            }
        }

        preg_match(
            '/^([0-9]{4})[.\/\-]([0-9]{1,2})[.\/\-]([0-9]{1,2})$/',
            $value,
            $match
        );

        if ($match) {
            list(, $year, $month, $day) = $match;

            if (30 >= $year) $year += 2000;
            else if (100 > $year) $year += 1900;

            if (static::checkDate($month, $day, $year)) {
                return mktime(12, 0, 0, $month, $day, $year);
            }
        }

        $year = 0;
        preg_match('/([0-9]{2,4}) ?(года|г\.|г)/', $value, $match);

        if ($match) {
            $year = $match[1];

            if (30 >= $year) $year += 2000;
            else if (100 > $year) $year += 1900;

            $value = trim(str_replace($match[0], '', $value));
        }

        $date = explode(' ', $value);

        if (
            1 < count($date) &&
            4 > count($date) &&
            !(3 == count($date) && 0 < $year)
        ) {
            $day = $date[0];
            $month = static::guessMonth($date[1]);

            if (isset($date[2])) $year = $date[2];
            else if (0 == $year) $year = date('Y');

            if (static::checkDate($month, $day, $year)) {
                return mktime(12, 0, 0, $month, $day, $year);
            }
        }

        return false;
    }

    public static function formatPeriod($_from,
                                        $_till = null,
                                        $_isTypo = true,
                                        $_isYear = false)
    {
        $ignoreTime = array('00:00:00', '23:59:59');
        $from = static::getDate($_from);
        $fromTime = in_array(date('H:i:s', $from), $ignoreTime)
                  ? ''
                  : date('H:i', $from);

        $till = empty($_till) ? $from : static::getDate($_till);
        $tillTime = in_array(date('H:i:s', $till), $ignoreTime)
                  ? ''
                  : date('H:i', $till);

        $spacer = $_isTypo ? '&nbsp;' : ' ';
        $dash = $_isTypo ? '&mdash;' : '—';
        $day = date('j', $from);
        $year = date('Y', $from);
        $nowYear = date('Y');
        $month = String::toLower(static::getMonth(date('m', $from), 2));
        $monthTill = String::toLower(static::getMonth(date('m', $till), 2));

        if (date('Ymd', $from) == date('Ymd', $till)) {
            $result = $day . $spacer . $month;

            if (!empty($_isYear) || $year != $nowYear) {
                $result .= " $year{$spacer}года";
            }

            if ($from != $till) {
                $result .= " $fromTime$dash$tillTime";
            }

        } else if (
            date('Ym', $from) == date('Ym', $till) ||
            $year == date('Y', $till)
        ) {
            $result = $day;

            if ($month != $monthTill) {
                $result .= $spacer . $month;
            }

            if ($fromTime) $result .= ' ' . $fromTime;
            $result .= $dash . date('j', $till) . $spacer . $monthTill;
            if ($tillTime) $result .= ' ' . $tillTime;

            if (!empty($_isYear) || $year != $nowYear) {
                $result .= " $year{$spacer}года";
            }

        } else {
            $result = "$day$spacer$month $year";
            if ($fromTime) $result .= ' ' . $fromTime;
            $result .= $dash . date('j', $till) . $spacer;
            $result .= $monthTill . ' ' . date('Y', $till);
            if ($tillTime) $result .= ' ' . $tillTime;
        }

        return $result;
    }

    public static function getDate($_date = null)
    {
        if (is_null($_date)) {
            return time();

        } else if (preg_match('/^[\d]+$/', $_date)) {
            return $_date;

        } else if (strpos($_date, '0000-00-00') === false) {
            $date = strtotime(str_replace('/', '-', $_date));
            return $date ? $date : false;

        } else {
            return false;
        }
    }

    public static function yesterday($_date = null)
    {
        return ($_date ?: self::today()) - self::DAY_SEC;
    }

    public static function today()
    {
        return mktime(0, 0, 0, date('n'), date('j'), date('Y'));
    }

    public static function tomorrow($_date = null)
    {
        return ($_date ?: self::today()) + self::DAY_SEC;
    }

    public static function getMonthFirstDay($_date = null)
    {
        $date = static::getDate($_date);
        return mktime(0, 0, 0, date('m', $date), 1, date('Y', $date));
    }

    public static function getMonthLastDay($_date = null)
    {
        $date = static::getDate($_date);
        return mktime(
            23, 59, 59,
            date('m', $date), date('t', $date), date('Y', $date)
        );
    }

    public static function getPreviousMonth($_date = null)
    {
        $date = static::getDate($_date);
        return mktime(0, 0, 0, date('m', $date), 0, date('Y', $date));
    }

    public static function getNextMonth($_date = null)
    {
        $date = static::getDate($_date);
        return mktime(
            0, 0, 0,
            date('m', $date),
            date('t', $date) + 1,
            date('Y', $date)
        );
    }

    public static function getWeekStart($_date = null)
    {
        $date = static::getDate($_date);
        return date('N', $date) == 1 ? $date : strtotime('last Monday', $date);
    }

    public static function getWeekEnd($_date = null)
    {
        $date = static::getDate($_date);
        return date('N', $date) == 7 ? $date : strtotime('next Sunday', $date);
    }

    public static function daysDiff($_from, $_till)
    {
        $from = static::getDate($_from);
        $from = mktime(
            0, 0, 0,
            date('n', $from),
            date('j', $from),
            date('Y', $from)
        );

        $till = static::getDate($_till);
        $till = mktime(
            0, 0, 0,
            date('n', $till),
            date('j', $till),
            date('Y', $till)
        );

        return floor(($till - $from) / self::DAY_SEC);
    }
}
