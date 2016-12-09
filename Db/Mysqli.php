<?php

namespace Ext\Db;

use \Ext\Str as Str;
use \Ext\Number;
use \Ext\File;

class Mysqli extends \Mysqli
{
    /**
     * Logs file.
     *
     * @var string
     */
    public $logFilePath = 'mysqli.log';

    /**
     * Logs on or off.
     *
     * @var bool
     */
    public $isLog = false;

    /**
     * Database user.
     *
     * @var string
     */
    protected $_user;

    /**
     * Database user password.
     *
     * @var string
     */
    protected $_password;

    /**
     * Database host.
     *
     * @var string
     */
    protected $_host;

    /**
     * Database port.
     *
     * @var string
     */
    protected $_port;

    /**
     * Database name.
     *
     * @var string
     */
    protected $_database;

    /**
     * @var string
     */
    protected $_prefix;

    /**
     * @var string
     */
    protected $_lastQuery;

    /**
     * Connects to database with passed connection string:
     * mysql://u:p@host/db?prefix=prfx_&set-names=utf8
     *
     * @param string $_connectionString
     */
    public function __construct($_connectionString)
    {
        $connectionString = '';

        for ($i = strlen($_connectionString) - 1; $i >= 0; $i--) {
            $append = $_connectionString{$i} == '@' &&
                      strpos($_connectionString, '@', $i + 1) !== false
                    ? '~Z~'
                    : $_connectionString{$i};

            $connectionString = $append . $connectionString;
        }

        $params = parse_url($connectionString);

        foreach (array('user', 'pass', 'host', 'port', 'path') as $item) {
            $params[$item] = isset($params[$item])
                           ? str_replace('~Z~', '@', $params[$item])
                           : '';
        }

        $this->_user     = $params['user'];
        $this->_password = $params['pass'];
        $this->_host     = $params['host'];
        $this->_port     = (int) $params['port'];
        $this->_database = trim($params['path'], '/');

        @parent::__construct($this->_host,
                             $this->_user,
                             $this->_password,
                             $this->_database,
                             $this->_port);

        $this->_throwIfError();

        if (!empty($params['query'])) {
            $query = array();

            foreach (explode('&', $params['query']) as $item) {
                list($name, $value) = explode('=', $item);

                if ($name && $value) {
                    $query[$name] = $value;
                }
            }

            if (!empty($query['set-names'])) {
                $this->execute('SET NAMES ' . $query['set-names']);
            }

            if (!empty($query['prefix'])) {
                $this->_prefix = $query['prefix'];
            }
        }
    }

    /**
     * Executes a query.
     *
     * @param string $_query
     * @return \mysqli_result
     */
    public function execute($_query)
    {
        if ($this->isLog) {
            list($msec, $sec) = explode(' ', microtime());
            $start = (float) $msec + (float) $sec;
        }

        $this->_lastQuery = $_query;
        $result = $this->query($_query);
        $this->_throwIfError();

        if (isset($start)) {
            list($msec, $sec) = explode(' ', microtime());
            $finish = (float) $msec + (float) $sec;
            $this->log($_query, $finish - $start);
        }

        return $result;
    }

    /**
     * Executes multiple queries.
     *
     * @param string $_query
     * @return \mysqli_result
     */
    public function multiExecute($_query)
    {
        if ($this->multi_query($_query)) {
            $result = array();

            do {
                $item = $this->store_result();

                if ($item) {
                    $row = $item->fetch_row();

                    while ($row) {
                        $result[] = $row;
                        $row = $item->fetch_row();
                    }

                    $item->free();
                }

            } while ($this->next_result());

            return $result;
        }

        return false;
    }

    /**
     * Executes a query and return associated array of the first entry.
     *
     * @param string $_query
     * @return array
     */
    public function getEntry($_query)
    {
        $res = $this->execute($_query);
        return $res && $res->num_rows > 0 ? $res->fetch_assoc() : false;
    }

    /**
     * Returns an array of result rows.
     *
     * @param string $_query
     * @param string $_options auto, one, few
     * @return array
     */
    public function getList($_query, $_options = 'auto')
    {
        $list = array();
        $result = $this->execute($_query);

        if ($result && $result->num_rows > 0) {
            $type = $_options;

            if ($type != 'one' && $type != 'few') {
                $type = $this->field_count == 1 ? 'one' : 'few';
            }

            if ($type == 'one') {
                $row = $result->fetch_row();

                while ($row) {
                    $list[] = $row[0];
                    $row = $result->fetch_row();
                }

            } else {
                $row = $result->fetch_assoc();

                while ($row) {
                    $list[] = $row;
                    $row = $result->fetch_assoc();
                }
            }
        }

        return $list;
    }

    /**
     * Gets last inserted ID.
     *
     * @return int
     */
    public function getLastInsertedId()
    {
        return $this->insert_id;
    }

    /**
     * @param string|int|array $_data
     * @param string $_quote
     * @return string
     */
    public function escape($_data, $_quote = null)
    {
        return is_array($_data)
             ? $this->escapeList($_data, $_quote)
             : $this->escapeValue($_data, $_quote);
    }

    /**
     * Prepares data to be posted into a query.
     *
     * @param string $_value
     * @param string $_quote
     * @return string
     */
    public function escapeValue($_value, $_quote = null)
    {
        $quote = empty($_quote) ? '\'' : $_quote;
        return Number::isNumber($_value)
             ? $_value
             : $quote . $this->real_escape_string($_value) . $quote;
    }

    /**
     * Prepares data to be posted into a query.
     *
     * @param array $_values
     * @param string $_quote
     * @return string
     */
    public function escapeList($_values, $_quote = null)
    {
        $result = array();

        foreach ($_values as $value) {
            $result[] = $this->escape($value, $_quote);
        }

        return implode(', ', $result);
    }

    /**
     * Returns list of fields for inserting into a query.
     *
     * @param array $_fields
     * @param string $_type
     * @param bool $_isEscaped
     * @return string
     */
    public function getQueryFields($_fields, $_type, $_isEscaped = false)
    {
        return $_isEscaped
             ? $this->getCustomQueryFields($_type, array(), $_fields)
             : $this->getCustomQueryFields($_type, $_fields, array());
    }

    /**
     * Returns list of fields for inserting into a query.
     *
     * @param string $type
     * @param array $parse
     * @param array $leave
     * @return string
     */
    public function getCustomQueryFields($type,
                                         array $parse = array(),
                                         array $leave = array())
    {
        foreach ($parse as $name => $value) {
            $parse[$name] = empty($value) && $value !== 0
                          ? 'NULL'
                          : $this->escape($value);
        }

        $fields = array_merge($parse, $leave);

        if ('insert' == $type) {
            return ' (`' . implode('`, `', array_keys($fields)) . '`) ' .
                   'VALUES (' . implode(', ', array_values($fields)) . ')';

        } else if ('update' == $type) {
            $result = '';

            foreach ($fields as $field => $value) {
                $result .= ('' == $result ? '' : ', ') . "`$field` = $value";
            }

            return " SET $result ";
        }

        return false;
    }

    /**
     * @param $_attrs
     * @param bool $_isEqual
     * @return array
     */
    public function getWhere($_attrs, $_isEqual = true)
    {
        $where = array();

        $comp1 = $_isEqual ? 'ISNULL' : '!ISNULL';
        $comp2 = $_isEqual ? 'IN' : 'NOT IN';
        $comp3 = $_isEqual ? '=' : '!=';

        foreach ($_attrs as $name => $value) {
            if (Number::isInteger($name)) {
                $where[] = $value;

//            } else if ($value === 'NULL' || empty($value)) {
            } else if ($value === null || $value === 'NULL' || $value === '') {
                $where[] = "($comp1($name) OR $name $comp3 '')";

            } else if (is_array($value)) {
                $where[] = "$name $comp2 (" . $this->escapeList($value) . ')';

            } else {
                $where[] = "$name $comp3 " . $this->escapeValue($value);
            }
        }

        return $where;
    }

    public function getWhereNot($_attrs)
    {
        return $this->getWhere($_attrs, false);
    }

    /**
     * @param string|array $_from
     * @param string|array $_attrs
     * @param array $_where
     * @param string $_order
     * @param int $_limit
     * @param int $_offset
     * @param string $_group
     * @return string
     */
    public function getSelect($_from,
                              $_attrs  = null,
                              $_where  = null,
                              $_order  = null,
                              $_limit  = null,
                              $_offset = null,
                              $_group  = null)
    {
        if (is_array($_from)) $from = implode(', ', $_from);
        else                  $from = $_from;

        if (empty($_attrs))          $attrs = '*';
        else if (!is_array($_attrs)) $attrs = $_attrs;
        else                         $attrs = implode(', ', $_attrs);

        if ($_where) {
            $where  = ' WHERE ';
            $where .= is_array($_where)
                    ? implode(' AND ', $this->getWhere($_where))
                    : $_where;

        } else {
            $where = '';
        }

        $order = $_order ? " ORDER BY $_order" : '';
        $group = $_group ? " GROUP BY $_group" : '';
        $limit = (int) $_limit ? ' LIMIT ' . (int) $_limit : '';
        $offset = (int) $_offset ? ' OFFSET ' . (int) $_offset : '';

        return 'SELECT ' . $attrs . ' FROM ' . $from .
               $where . $group . $order . $limit . $offset;
    }

    /**
     * Returns next auto increment integer value.
     *
     * @param string $_table
     * @param string $_field
     * @param string $_where
     * @return int
     */
    public function getNextNumber($_table, $_field, $_where = null)
    {
        $result = $this->getEntry($this->getSelect(
            $_table,
            $_field,
            $_where,
            "$_field DESC",
            1
        ));

        return (int) $result[$_field] + 1;
    }

    /**
     * Returns unique string for field in table.
     *
     * @param string $_table
     * @param string $_field
     * @param int $_length
     * @return string
     */
    public function getUnique($_table, $_field = null, $_length = null)
    {
        $field = is_null($_field) ? $_table . '_id' : $_field;
        $length = is_null($_length) ? 30 : $_length;
        $unique = false;

        while (true) {
            $unique = Str::getRandom($length);

            if (!$this->getEntry($this->getSelect(
                $_table,
                $field,
                array($field => $unique)
            ))) {
                break;
            }
        }

        return $unique;
    }

    /**
     * Appends query with additional info to log file.
     *
     * @param string $_value
     * @param float $_time
     */
    public function log($_value, $_time = null)
    {
        if ($this->isLog) {
            $log = array(
                date('Y-m-d H:i:s'),
                 $_time ? number_format($_time, 4) : '',
                 preg_replace('/\s+/', ' ', trim($_value))
            );

            $params = array(
                'REQUEST_URI',
                'REMOTE_ADDR',
                'HTTP_X_REAL_IP',
                'HTTP_USER_AGENT'
            );

            foreach ($params as $i) {
                if (!empty($_SERVER[$i])) {
                    $log[] = $_SERVER[$i];
                }
            }

            File::log($this->logFilePath, $log);
        }
    }

    /**
     * Defines errors output. First try to output through framework function.
     *
     * @param $_error
     * @throws \Exception
     */
    protected function _error($_error)
    {
        $error = $_error;

        if ($this->_lastQuery) {
            $error .= ': ' . $this->_lastQuery;
        }

        throw new \Exception($error);
    }

    protected function _throwIfError()
    {
        if ($this->connect_error) {
            $this->_error(
                'MySQL connection error (' . $this->connect_errno . ') "' .
                $this->connect_error . '"'
            );

        } else if (mysqli_connect_error()) {
            $this->_error(
                'Connect error (' . mysqli_connect_errno() . ') "' .
                mysqli_connect_error() . '"'
            );

        } else if ($this->error) {
            $this->_error(
                'Connect error (' . $this->errno . ') "' . $this->error . '"'
            );
        }
    }

    /**
     * Returns database user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns database password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Returns database host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Returns database port.
     *
     * @return string
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Returns database name.
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (!empty($this->host_info)) {
            $this->close();
        }

        $this->log('Mysqli descructor.');
    }
}
