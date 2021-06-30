<?php

namespace Ext\Db;

use \Ext\Db;
use \Ext\Db\ActiveRecord\Attribute;
use \Ext\Str as Str;
use \Ext\Date;
use \Ext\Xml;
use \Ext\File;

class ActiveRecord extends \StdClass
{
    /** @var string */
    protected $_table;

    /** @var ActiveRecord\Attribute[] */
    protected $_attributes;

    /** @var self[] */
    protected $_foreignInstances = array();

    /** @var array[] */
    protected $_links = array();

    /** @var \Ext\File[] */
    protected $_files;

    /** @var \Ext\Image[] */
    protected $_images;

    /**
     * @param string $_table
     */
    public function __construct($_table = null)
    {
        $this->_table = is_null($_table) ? static::computeTable() : $_table;
    }

    /**
     * @return self|Model
     */
    public static function createInstance()
    {
        $class = get_called_class();
        return new $class;
    }

    /**
     * @return string
     */
    public static function computeTable()
    {
//        $name = str_replace(array('Ext_'), '', get_called_class());
//        return Db::get()->getPrefix() . Str::underline($name);
        return Db::get()->getPrefix() . Str::underline(get_called_class());
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * @return string
     */
    public static function getTbl()
    {
        return static::createInstance()->getTable();
    }

    /**
     * @param string $_name
     * @return bool
     */
    public function hasAttr($_name)
    {
        return array_key_exists($_name, $this->_attributes) ||
               array_key_exists(Str::underline($_name), $this->_attributes);
    }

    /**
     * @param string $_name
     * @return bool
     */
    public function __isset($_name)
    {
        return property_exists($this, $_name) || $this->hasAttr($_name);
    }

    /**
     * Преобразоваывает и ищет атрибут, чтобы вернут его название. Имя id
     * преобразовывается в первичный ключ, *_id преобразовывается в первичный
     * ключ внешней таблицы.
     *
     * @param string $_name
     * @return string|array|bool
     */
    public function getAttrName($_name)
    {
        if (!property_exists($this, $_name)) {
            if (array_key_exists($_name, $this->_attributes)) {
                return $_name;
            }

            if ($_name == 'id') {
                return $this->getPrimaryKey()->getName();
            }

            $name = Str::underline($_name);

            if (array_key_exists($name, $this->_attributes)) {
                return $name;
            }

            if (Db::get()->getPrefix()) {
                $name = Db::get()->getPrefix() . $name;

                if (array_key_exists($name, $this->_attributes)) {
                    return $name;
                }
            }
        }

        return false;
    }

    /**
     * @param string $_name
     * @return string|number
     */
    public function __get($_name)
    {
        return property_exists($this, $_name)
             ? $this->$_name
             : $this->getAttrValue($_name);
    }

    /**
     * @param string $_name
     * @param string|number $_value
     */
    public function __set($_name, $_value)
    {
        if (property_exists($this, $_name)) {
            $this->$_name = $_value;

        } else {
            $this->setAttrValue($_name, $_value);
        }
    }

    /**
     * @param string $_name
     * @return Attribute
     * @throws \Exception
     */
    public function getAttr($_name)
    {
        $name = $this->getAttrName($_name);
        if ($name) {
            return $this->_attributes[$name];
        }

        throw new \Exception("There is no a such property `$_name`.");
    }

    /**
     * @param string $_name
     * @return string|number
     */
    public function getAttrValue($_name)
    {
        return $this->getAttr($_name)->getValue();
    }

    /**
     * @param string $_name
     * @param string|number $_value
     */
    public function setAttrValue($_name, $_value)
    {
        $this->getAttr($_name)->setValue($_value);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $attrs = array();

        foreach ($this->_attributes as $attr) {
            $attrs[$attr->getName()] = $attr->getValue();
        }

        return $attrs;
    }

    /**
     * @param string|bool $_prepend
     * @return array
     */
    public function getAttrNames($_prepend = false)
    {
        if ($_prepend) {
            $names = array();

            if ($_prepend === true)       $prepend = '`' . $this->getTable() . '`.';
            else if (!is_null($_prepend)) $prepend = "`$_prepend`.";
            else                          $prepend = '';

             foreach (array_keys($this->_attributes) as $name) {
                 $names[$name] = $prepend . $name;
             }

             return $names;

        } else {
            return array_keys($this->_attributes);
        }
    }

    /**
     * @param string $_name
     * @param string $_type
     * @return Attribute
     */
    public function addAttr($_name, $_type)
    {
        $this->_attributes[$_name] = new Attribute($_name, $_type);
        return $this->_attributes[$_name];
    }

    /**
     * @param $_name
     */
    public function deleteAttr($_name)
    {
        unset($this->_attributes[$_name]);
    }

    /*
     * @param string $_name
     * @param string $_type
     * @return Attribute
     * @throws \Exception
     */
    public function addPrimaryKey()
    {
        if (func_num_args() == 1) {
            $attr = $this->addAttr(
                $this->computePrimaryKeyName(),
                func_get_arg(0)
            );

        } else if (func_num_args() == 2) {
            $attr = $this->addAttr(func_get_arg(0), func_get_arg(1));

        } else {
            throw new \Exception('Wrong number of arguments.');
        }

        $attr->isPrimary(true);
        return $attr;
    }

    /**
     * Beware of recursion.
     *
     * @param self $_instance
     * @return Attribute
     */
    public function addForeign(ActiveRecord $_instance, $_name = null)
    {
        $key = $_instance->getPrimaryKey();
        $name = $_name ?: $key->getName();
        $this->_foreignInstances[$name] = $_instance;

        return $this->addAttr($name, $key->getType());
    }

    /**
     * @return ActiveRecord[]
     */
    public function getForeignInstances()
    {
        return $this->_foreignInstances;
    }

    /**
     * @return Attribute|Attribute[]
     * @throws \Exception
     */
    public function getPrimaryKey()
    {
        $keys = array();

        if (!$this->_attributes) {
            throw new \Exception();
        }

        foreach ($this->_attributes as $item) {
            if ($item->isPrimary()) {
                $keys[$item->getName()] = $item;
            }
        }

        $cnt = count($keys);

        if ($cnt == 0)      throw new \Exception('Primary key must be set');
        else if ($cnt == 1) return current($keys);
        else                return $keys;
    }

    /**
     * @param string $_prepend
     * @return string|array
     */
    public function getPrimaryKeyName($_prepend = null)
    {
        if ($_prepend === true)       $prepend = $this->getTable() . '.';
        else if (!is_null($_prepend)) $prepend = "$_prepend.";
        else                          $prepend = '';

        $primary = $this->getPrimaryKey();

        if (is_array($primary)) {
            /** @var Attribute[] $primary */
            foreach ($primary as $name => $attr) {
                $primary[$name] = $prepend . $attr->getName();
            }

            return $primary;

        } else {
            return $prepend . $primary->getName();
        }
    }

    /**
     * @param string $_prepend
     * @return string|array
     */
    public static function getPri($_prepend = null)
    {
        return static::createInstance()->getPrimaryKeyName($_prepend);
    }

    /**
     * @param string $_prepend
     * @return string
     */
    public static function getFirstForeignPri($_prepend = null)
    {
        $keys = array_values(static::getPri($_prepend));
        return $keys[0];
    }

    /**
     * @return string
     */
    public static function getFirstForeignTbl()
    {
        return current(
            static::createInstance()->getForeignInstances()
        )->getTable();
    }

    /**
     * @param string $_prepend
     * @return string
     */
    public static function getSecondForeignPri($_prepend = null)
    {
        $keys = array_values(static::getPri($_prepend));
        return $keys[1];
    }

    /**
     * @return string
     */
    public static function getSecondForeignTbl()
    {
        /** @var ActiveRecord[] $instances */
        $instances = array_values(
            static::createInstance()->getForeignInstances()
        );

        return $instances[1]->getTable();
    }

    /**
     * @return string
     */
    public function computePrimaryKeyName()
    {
        return $this->getTable() . '_id';
    }

    /**
     * @param string|array $_value
     * @param bool $_isEqual
     * @return string
     */
    public function getPrimaryKeyWhere($_value = null, $_isEqual = true)
    {
        $where = array();
        $primary = $this->getPrimaryKey();
        $comp = $_isEqual ? '=' : '!=';

        if (is_array($primary)) {
            /** @var Attribute[] $primary */
            foreach ($primary as $name => $attr) {
                $value = is_null($_value)
                       ? $attr->getSqlValue()
                       : Db::escape($_value[$name]);

                $where[] = $attr->getName() . " $comp $value";
            }

        } else {
            $value = is_null($_value)
                   ? $primary->getSqlValue()
                   : Db::escape($_value);

            $where[] = $primary->getName() . " $comp $value";
        }

        return implode(' AND ', $where);
    }

    /**
     * @param string $_value
     * @return string
     */
    public function getPrimaryKeyWhereNot($_value = null)
    {
        return $this->getPrimaryKeyWhere($_value, false);
    }

    /**
     * @todo Замерить что работает быстрее $this->getId() или $this->id?
     * @param bool $_isSql
     * @return string|array[string]
     */
    public function getId($_isSql = null)
    {
        $primary = $this->getPrimaryKey();

        if (is_array($primary)) {
            $ids = array();

            /** @var Attribute[] $primary */
            foreach ($primary as $attr) {
                $ids[$attr->getName()] = $_isSql
                                       ? $attr->getSqlValue()
                                       : $attr->getValue();
            }

            return $ids;

        } else {
            return $_isSql ? $primary->getSqlValue() : $primary->getValue();
        }
    }

    /**
     * @return string|number
     */
    public function getSqlId()
    {
        return $this->getId(true);
    }

    /**
     * @param string|int $_id
     * @return self|Model|bool
     */
    public static function getById($_id)
    {
        return static::fetch($_id);
    }

    /**
     * @param string $_attr
     * @param string|int $_value
     * @return self|Model|bool
     */
    public static function getBy($_attr, $_value)
    {
        return static::fetch($_value, $_attr);
    }

    /**
     * @param string $_name
     * @return self|Model|bool
     */
    public static function getByName($_name)
    {
        return static::getBy('name', $_name);
    }

    /**
     * @param string|int $_value
     * @param string $_attr
     * @return self|Model|bool
     */
    public static function fetch($_value, $_attr = null)
    {
        $obj = static::createInstance();
        $data = $obj->fetchArray($_value, $_attr);

        if ($data !== false) {
            $obj->fillWithData($data);
            return $obj;
        }

        return false;
    }

    /**
     * @param string|int|array $_value
     * @param string|array $_attr
     * @return array|false
     */
    public function fetchArray($_value, $_attr = null)
    {
        if (is_array($_attr)) {
            $tmp = array();

            foreach ($_attr as $i => $attr) {
                $tmp[] = "$attr = " . Db::escape(
                             isset($_value[$attr]) ? $_value[$attr] : $_value[$i]
                         );
            }

            $where = implode(' AND ', $tmp);

        } else if ($_attr) {
            $where = "$_attr = " . Db::escape($_value);

        } else {
            $where = $this->getPrimaryKeyWhere($_value);
        }

        return Db::get()->getEntry("
            SELECT * FROM `{$this->_table}` WHERE $where LIMIT 1
        ");
    }

    /**
     * @param array $_data
     */
    public function fillWithData(array $_data)
    {
        foreach ($this->_attributes as $item)
            if (array_key_exists($item->getName(), $_data))
                $item->setValue($_data[$item->getName()]);
    }

    /**
     * @return bool
     */
    public function save()
    {
        return $this->id ? $this->update() : $this->create();
    }

    /**
     * @return bool
     */
    public function create()
    {
        $values = array();

        foreach ($this->_attributes as $item) {
            if (!$item->isValue()) {
                if ($item->isPrimary()) {
                    if ($item->getType() == 'string') {
                        $item->setValue(Db::get()->getUnique(
                            $this->getTable(),
                            $item->getName(),
                            $item->getLength() ? $item->getLength() : null
                        ));
                    }

                } else if (
                    $item->getName() == 'sort_order' &&
                    $this->getPrimaryKey()->getType() != 'integer'
                ) {
                    $item->setValue(Db::get()->getNextNumber(
                        $this->getTable(),
                        $item->getName()
                    ));

                } else if (
                    $item->getName() == 'creation_date' ||
                    $item->getName() == 'creation_time'
                ) {
                    if ($item->getType() == 'integer') {
                        $item->setValue(time());
                    } else {
                        $item->setValue(date('Y-m-d H:i:s'));
                    }

                } else if (strpos($item->getName(), 'is_') === 0) {
                    $item->setValue(0);
                }
            }

            $values[$item->getName()] = $item->getSqlValue();
        }

        $result = Db::get()->execute(
            'INSERT INTO `' . $this->getTable() . '`' .
            Db::get()->getQueryFields($values, 'insert', true)
        );

        if ($result) {
            $lastId = Db::get()->getLastInsertedId();

            if ($lastId) {
                if (!$this->id) $this->id = $lastId;

                if (
                    $this->hasAttr('sort_order') &&
                    !$this->sortOrder &&
                    $this->getPrimaryKey()->getType() == 'integer'
                ) {
                    $this->updateAttr('sort_order', $lastId);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function update()
    {
        $attrs = array();

        foreach ($this->_attributes as $attr) {
            if (!$attr->isPrimary()) {
                $attrs[$attr->getName()] = $attr->getSqlValue();
            }
        }

        return (bool) Db::get()->execute(
            "UPDATE `{$this->_table}`" .
            Db::get()->getQueryFields($attrs, 'update', true) .
            'WHERE ' . $this->getPrimaryKeyWhere() . ' LIMIT 1'
        );
    }

    /**
     * @param string $_name
     * @param string|number $_value
     * @return bool
     */
    public function updateAttr($_name, $_value = null)
    {
        return $this->updateAttrs([$_name => $_value]);
    }

    /**
     * @param array $_attrs
     * @return bool
     */
    public function updateAttrs($_attrs)
    {
        $attrs = [];

        foreach ($_attrs as $name => $value) {
            if (!is_null($value)) $this->$name = $value;
            $attrs[$name] = $this->getAttr($name)->getSqlValue();
        }

        return (bool) Db::get()->execute(
            "UPDATE `{$this->_table}`" .
            Db::get()->getQueryFields($attrs, 'update', true) .
            'WHERE ' . $this->getPrimaryKeyWhere() . ' LIMIT 1'
        );
    }

    /**
     * @param array $_items
     */
    protected function _deleteFiles($_items)
    {
        if (is_array($_items)) {
            foreach ($_items as $item) {
                if (is_array($item)) $this->_deleteFiles($item);
                else if ($item instanceof File) $item->delete();
            }
        }
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if (isset($this->_links))
            foreach (array_keys($this->_links) as $item)
                $this->updateLinks($item);

        $this->_deleteFiles($this->getFiles());

        return (bool) Db::get()->execute(
            "DELETE FROM `{$this->_table}` WHERE " .
            $this->getPrimaryKeyWhere() . ' LIMIT 1'
        );
    }

    /**
     * @return bool
     */
    public static function truncate()
    {
        return (bool) Db::get()->execute('TRUNCATE `' . static::getTbl() . '`');
    }

    /**
     * @param array $_where
     * @return bool
     */
    public static function deleteWhere($_where)
    {
        return (bool) Db::get()->execute(
            'DELETE FROM `' . static::getTbl() . '`' .
            ' WHERE ' . implode(' AND ', Db::get()->getWhere($_where))
        );
    }

//     Метод давно не использовался, поэтому его актуальность под вопросом.
//
//     public static function tableInit($_table, $_id = null, $_isLog = false)
//     {
//         $className = get_called_class();
//         $obj = new $className($_table);
//
//         if ($_isLog) {
//             $logFile = LIBRARIES . File::computeName($className) . '.txt';
//             File::write($logFile, $_table . PHP_EOL . PHP_EOL);
//             File::write(
//                 $logFile,
//                 'static::$Base = new ActiveRecord(static::TABLE);' . PHP_EOL
//             );
//         }
//
//         $attributes = Db::get()->getList("SHOW COLUMNS FROM $_table");
//
//         foreach ($attributes as $item) {
//             if ($item['Type'] == 'tinyint(1)') {
//                 $type = 'bool';
//
//             } else if (preg_match(
//                 '/^([a-zA-Z]+)\((.+)\)$/',
//                 $item['Type'],
//                 $match
//             )) {
//                 $type = $match[1];
//
//             } else {
//                 $type = $item['Type'];
//             }
//
//             $method = (strpos($item['Key'], 'PRI') !== false)
//                     ? 'addPrimaryKey'
//                     : 'addAttr';
//
//             if ($_isLog) {
//                 File::write(
//                     $logFile,
//                     "static::\$_base->$method('{$item['Field']}', '$type');" .
//                     PHP_EOL
//                 );
//             }
//
//             $obj->$method($item['Field'], $type);
//         }
//
//         if ($_id) {
//             $obj->retrieve($_id);
//         }
//
//         return $obj;
//     }

    /**
     * @return string|bool
     */
    public function getSortAttrName()
    {
        foreach (array('sort_order', 'title', 'name') as $name) {
            if ($this->hasAttr($name)) {
                return $name;
            }
        }

        return false;
    }

    /**
     * @param array $_where
     * @param array $_params
     * @return ActiveRecord[]
     */
    public static function fetchList($_where = null, $_params = array())
    {
        $instance = static::createInstance();
        $list = array();

        $items = Db::get()->getList(Db::get()->getSelect(
            $instance->getTable(),
            null,
            $_where,
            empty($_params['order'])  ? $instance->getSortAttrName() : $_params['order'],
            empty($_params['limit'])  ? null : (int) $_params['limit'],
            empty($_params['offset']) ? null : (int) $_params['offset'],
            empty($_params['group'])  ? null : $_params['group']
        ));

        foreach ($items as $item) {
            $obj = static::createInstance();
            $obj->fillWithData($item);
            $list[is_array($obj->getId()) ? implode('-', $obj->getId()) : $obj->getId()] = $obj;
        }

        return $list;
    }

    /**
     * @param array $_where
     * @return int
     */
    public static function getCount($_where = array())
    {
        $result = Db::get()->getEntry(Db::get()->getSelect(
            static::getTbl(),
            'COUNT(1) AS `cnt`',
            $_where
        ));

        return $result ? (int) $result['cnt'] : 0;
    }

    /**
     * @param string $_attr
     * @param string $_value
     * @param string|array $_excludeId
     * @return bool
     */
    public static function isUnique($_attr, $_value, $_excludeId = null)
    {
        $where = array($_attr => $_value);

        if ($_excludeId) {
            $where = array_merge(
                $where,
                Db::get()->getWhereNot(array(static::getPri() => $_excludeId))
            );
        }

        return count(static::getList($where, array('limit' => 1))) == 0;
    }

    /**
     * @param string $_name
     * @param array $_value
     */
    public function updateLinks($_name, $_value = null)
    {
        if ($this->getLinks($_name)) {
            foreach ($this->getLinks($_name) as $item) {
                $item->delete();
            }

            $this->setLinks($_name);
        }

        if (!empty($_value)) {
            $this->setLinks($_name, $_value);

            foreach ($this->getLinks($_name) as $item) {
                $item->create();
            }
        }
    }

    /**
     * @param string $_name
     * @param bool $_isPublished
     * @return self[]
     */
    public function getLinks($_name, $_isPublished = null)
    {
        if (!isset($this->_links[$_name])) {
            if (isset($this->_linkParams[$_name])) {
                $class = $this->_linkParams[$_name];
                $where = array($this->getPrimaryKeyWhere());

                if (!is_null($_isPublished)) {
                    $where['is_published'] = (bool) $_isPublished ? 1 : 0;
                }

//                $this->_links[$_name] = $class::getList($where);
                $this->_links[$_name] = call_user_func_array(
                    array($class, 'getList'),
                    $where
                );

            } else {
                $this->_links[$_name] = array();
            }
        }

        return $this->_links[$_name];
    }

    public function getLinkIds($_name, $_isPublished = null)
    {
        $result = array();

        if (isset($this->_linkParams[$_name])) {
            $class = $this->_linkParams[$_name];

//            $keys = array(
//                $class::getFirstForeignPri(),
//                $class::getSecondForeignPri()
//            );
            $keys = array(
                call_user_func(array($class, 'getFirstForeignPri')),
                call_user_func(array($class, 'getSecondForeignPri'))
            );

            $key = $this->getPrimaryKeyName() == $keys[0]
                 ? $keys[1]
                 : $keys[0];

            foreach ($this->getLinks($_name, $_isPublished) as $item) {
                $result[] = $item->$key;
            }
        }

        return $result;
    }

    public function setLinks($_name, $_values = null)
    {
        $this->_links[$_name] = array();

        if (!empty($_values) && isset($this->_linkParams[$_name])) {
            $values = is_array($_values) ? $_values : array($_values);
            $class = $this->_linkParams[$_name];

//            $keys = array(
//                $class::getFirstForeignPri(),
//                $class::getSecondForeignPri()
//            );
            $keys = array(
                call_user_func(array($class, 'getFirstForeignPri')),
                call_user_func(array($class, 'getSecondForeignPri'))
            );

            $pri = $this->getPrimaryKeyName();
            $key = $pri == $keys[0] ? $keys[1] : $keys[0];

            foreach ($values as $id => $item) {
                $obj = new $class;
                $obj->$pri = $this->id;

                if (is_array($item)) {
                    $obj->$key = $id;

                    foreach ($item as $attribute => $value) {
                        $obj->$attribute = $value;
                    }

                } else {
                    $obj->$key = $item;
                }

                $this->_links[$_name][] = $obj;
            }
        }
    }

    public static function getList($_where = null, $_params = array())
    {
        return static::fetchList($_where, $_params);
    }

    public function getTitle()
    {
        if (isset($this->title) && $this->title)    return $this->title;
        else if (isset($this->name) && $this->name) return $this->name;
        else                                        return 'ID ' . $this->id;
    }

    public function getDate($_name)
    {
        return !empty($this->$_name) ? Date::getDate($this->$_name) : false;
    }

    public function setDate($_name, $_value)
    {
        $date = Date::getDate($_value);
        $this->$_name = date('Y-m-d H:i:s', $date);
        return $date;
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $node = $_node ? $_node : Str::dash($this->getTable());

        if (empty($_xml))         $xml = array();
        else if (is_array($_xml)) $xml = $_xml;
        else                      $xml = array($_xml);

        if (!array_key_exists('title', $xml))
            Xml::append($xml, Xml::cdata('title', $this->getTitle()));

        $attrs = empty($_attrs) ? array() : $_attrs;

        if (!array_key_exists('id', $attrs))
            $attrs['id'] = $this->id;

        return Xml::node($node, $xml, $attrs);
    }

    public function getBackOfficeXml($_xml = array(), $_attrs = array())
    {
        $attrs = $_attrs;

        if (
            !isset($attrs['is_published']) && (
                ($this->hasAttr('is_published') && $this->isPublished) ||
                ($this->hasAttr('status_id') && $this->statusId == 1)
            )
        ) {
            $attrs['is-published'] = 1;
        }

        return $this->getXml('item', $_xml, $attrs);
    }

    public function getFiles()
    {
        if (is_null($this->_files)) {
            $this->_files = array();

            if (
                method_exists($this, 'getFilePath') &&
                $this->getFilePath() &&
                is_dir($this->getFilePath())
            ) {
                $handle = opendir($this->getFilePath());

                while (false !== $item = readdir($handle)) {
                    $filePath = rtrim($this->getFilePath(), '/') . '/' . $item;

                    if (strpos($item, '.') !== 0 && is_file($filePath)) {
                        $file = File::factory($filePath);

                        $this->_files[Str::toLower($file->getFilename())] = $file;
                    }
                }

                closedir($handle);
            }
        }

        return $this->_files;
    }

    public function getFileByFilename($_filename)
    {
        $files = $this->getFiles();

        return $files && array_key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getFileByName($_name)
    {
        foreach ($this->getFiles() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getFile($_name)
    {
        $file = $this->getFileByName($_name);

        if (!$file) {
            $file = $this->getFileByFilename($_name);
        }

        return $file;
    }

    public function getImages()
    {
        if (is_null($this->_images)) {
            $this->_images = array();

            foreach ($this->getFiles() as $key => $file) {
                if ($file->isImage()) {
                    $this->_images[$key] = $file;
                }
            }
        }

        return $this->_images;
    }

    public function getIlluByFilename($_filename)
    {
        $files = $this->getImages();

        return $files && array_key_exists($_filename, $files)
             ? $files[$_filename]
             : false;
    }

    public function getIlluByName($_name)
    {
        foreach ($this->getImages() as $file) {
            if ($_name == $file->getName()) {
                return $file;
            }
        }

        return false;
    }

    public function getIllu($_name)
    {
        $illu = $this->getIlluByName($_name);

        if (!$illu) {
            $illu = $this->getIlluByFilename($_name);
        }

        return $illu;
    }

    public function resetFiles()
    {
        $this->_files = null;
        $this->_images = null;
    }

    public function cleanFileCache()
    {
        foreach ($this->getFiles() as $file)
            File\Cache::delete($file->getPath());
    }

    public function uploadFile($_filename, $_tmpName, $_newName = null)
    {
        if (!method_exists($this, 'getFilePath')) {
            throw new \Exception('Method getFilePath must be implemented.');
        }

        $filename = is_null($_newName)
                  ? File::normalizeName($_filename)
                  : $_newName . '.' . File::computeExt($_filename);

        $path = $this->getFilePath() . $filename;

        File::deleteFile($path);
        File::createDir($this->getFilePath());

        move_uploaded_file($_tmpName, $path);
        File::chmod($path, 0777);

        File\Cache::delete($path);

        return $path;
    }
}
