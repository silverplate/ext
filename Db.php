<?php

namespace Ext;

use \Ext\Db\Mysqli;

class Db
{
    /**
     * @var \Ext\Db\Mysqli
     */
    protected static $_db;

    /**
     * @return \Ext\Db\Mysqli
     */
    public static function get()
    {
        if (!isset(static::$_db)) static::init();
        return static::$_db;
    }

    /**
     * @param \Ext\Db\Mysqli $_db
     */
    public static function set($_db)
    {
        static::$_db = $_db;
    }

    /**
     * @param string $_connectionString "mysql://user:password@host/database"
     * @param array|null $_sslOptions
     * @return \Ext\Db\Mysqli
     * @throws \Exception
     */
    public static function init($_connectionString = null, array $_sslOptions = null)
    {
        global $gDbConnectionString, $gDbSslOptions;

        $str = $_connectionString ?: $gDbConnectionString;
        if (empty($str)) {
            throw new \Exception('There are no params for connection.');
        }

        static::set($db = new Mysqli($str, $_sslOptions ?: $gDbSslOptions));
        return static::get();
    }

    /**
     * @param string|integer|array $_data
     * @param string $_quote
     * @return string
     */
    public static function escape($_data, $_quote = null)
    {
        return static::get()->escape($_data, $_quote);
    }

    /**
     * @param string $_sourceFile Path to future dump file.
     * @return string
     */
    public static function dump($_sourceFile)
    {
        $user = static::get()->getUser();
        $password = static::get()->getPassword();
        $host = static::get()->getHost();
        $database = static::get()->getDatabase();
        $connectionParams = "-u$user -p$password -h$host";

        if (static::get()->getPort()) {
            $connectionParams .= ' -P' . static::get()->getPort();
        }

        return exec("mysqldump $connectionParams $database > $_sourceFile");
    }
}
