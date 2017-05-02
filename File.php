<?php

namespace Ext;

class File
{
    protected $_path;
    protected $_dir;
    protected $_uri;
    protected $_filename;
    protected $_name;
    protected $_ext;
    protected $_pathStartsWith;
    protected $_uriStartsWith;
    protected $_mime;
    protected $_size;

    /**
     * @var array Двухбуквенное обозначение языка (например, en).
     * Если язык не задан, то используется только русский язык.
     */
    protected static $_langs = array();

    /** @var array */
    protected static $_url;

    /**
     * @param string $_lang
     */
    public static function addLang($_lang)
    {
        static::$_langs[] = $_lang;
    }

    public static function getLangs()
    {
        return static::$_langs;
    }

    public static function createDir($_dir, $_isRecursive = true)
    {
        if (!is_dir($_dir)) {
            $mask = umask(0);
            mkdir($_dir, 0777, $_isRecursive);
            umask($mask);
        }
    }

    public static function deleteDir($_dir, $_deleteEmptyAncestors = false)
    {
        if (!is_dir($_dir)) return false;
        $items = array_diff(scandir($_dir), array('.', '..'));
        $dir = rtrim($_dir, '/') . '/';

        foreach ($items as $item) {
            if (is_dir($dir . $item)) {
                static::deleteDir($dir . $item, $_deleteEmptyAncestors);

            } else unlink($dir . $item);
        }

        if (rmdir($_dir)) {
            if ($_deleteEmptyAncestors) {
                $parent = dirname($_dir);

                if (static::isDirEmpty($parent)) {
                    return static::deleteDir($parent, $_deleteEmptyAncestors);
                }
            }

            return true;
        }

        return false;
    }

    public static function isDirEmpty($_dir, $_ignore = null)
    {
        $ignore = $_ignore
                ? (is_array($_ignore) ? $_ignore : array($_ignore))
                : array();

        $ignore = array_merge(
            $ignore,
            array('.', '..', '.DS_Store')
        );

        return is_dir($_dir) && 0 == count(array_diff(scandir($_dir), $ignore));
    }

    /**
     * @param string $_dir
     * @param string $_name
     * @param string $_class
     * @return File
     */
    public static function getByName($_dir, $_name, $_class = null)
    {
        $dir = rtrim($_dir, '/') . '/';

        if (is_dir($dir)) {
            $class = is_null($_class) ? get_called_class() : $_class;

            foreach (array('.*', '*') as $try) {
                $search = glob($dir . $_name . $try);

                if ($search) {
                    return new $class($search[0]);
                }
            }
        }

        return false;
    }

    public static function moveFile($_from, $_to)
    {
        if (is_file($_from)) {
            static::deleteFile($_to);
            return rename($_from, $_to);
        }

        return false;
    }

    public static function moveDir($_from, $_to)
    {
        if (is_dir($_from)) {
            static::deleteDir($_to);
            return rename($_from, $_to);
        }

        return false;
    }

    public static function computeFilename($_file)
    {
        return pathinfo($_file, PATHINFO_BASENAME);
    }

    public static function computeName($_file)
    {
        return pathinfo($_file, PATHINFO_FILENAME);
    }

    public static function computeExt($_file)
    {
        return pathinfo($_file, PATHINFO_EXTENSION);
    }

    public static function computeSizeMeasure($_size)
    {
        $result = array();

        if ($_size / (1024 * 1024) > 0.5) {
            $result['value'] = $_size / (1024 * 1024);
            $result['measure'] = 'МБ';
            $result['measure-en'] = 'MB';

        } else if ($_size / 1024 > 0.5) {
            $result['value'] = $_size / 1024;
            $result['measure'] = 'КБ';
            $result['measure-en'] = 'KB';

        } else {
            $result['value'] = $_size;
            $result['measure'] = 'байт';
            $result['measure-en'] = 'bite';
        }

        $result['value'] = Number::format($result['value']);
        $result['string'] = $result['value'] . ' ' . $result['measure'];
        $result['string-en'] = $result['value'] . ' ' . $result['measure-en'];

        return $result;
    }

    public static function compressFile($_srcName, $_dstName)
    {
        $fp = fopen($_srcName, 'r');
        $data = fread($fp, filesize($_srcName));
        fclose($fp);

        $zp = gzopen($_dstName, 'w9');
        gzwrite($zp, $data);
        gzclose($zp);
    }

    public static function normalizeName($_name, $_noDots = false)
    {
        $name = strip_tags($_name);
        $name = html_entity_decode($name, ENT_NOQUOTES, 'utf-8');
        $name = strtolower(Str::translit($name));
        $name = preg_replace('/[^\s\-a-z.0-9_]/', '', $name);
        $name = preg_replace('/_+/', '-', $name);
        $name = preg_replace('/\s+/', '-', $name);

        if ($_noDots) {
            $name = str_replace('.', '-', $name);

        } else {
            $ext = static::computeExt($name);

            if ($ext) {
                $name = str_replace('.', '-', static::computeName($name)) .
                        '.' . $ext;
            }
        }

        $name = preg_replace('/-+/', '-', $name);
        return $name;
    }

    public static function normalizeDirName($_name)
    {
        return static::normalizeName($_name, true);
    }

    /**
     * @param string $_name
     * @return bool
     */
    public static function checkName($_name)
    {
        return preg_match('/^[\-a-z.0-9]+$/', $_name) > 0;
    }

    public static function deleteFile($_filePath)
    {
        return is_file($_filePath) ? unlink($_filePath) : false;
    }

    /**
     * @param string $_filePath
     * @param array|string $_content
     * @return integer|bool
     */
    public static function log($_filePath, $_content)
    {
        $log = array(date('Y-m-d H:i:s'));

        if (is_array($_content)) {
            foreach ($_content as $key => $item) {
                $item = trim($item);

                if (!is_int($key)) $log[] = $key;
                $log[] = strpos($item, "\t") === false ? $item : "\"$item\"";
            }

        } else {
            $log[] = $_content;
        }

        return static::append($_filePath, implode("\t", $log) . PHP_EOL);
    }

    /**
     * @param string $_filePath
     * @param string $_content
     * @return integer|bool
     */
    public static function append($_filePath, $_content)
    {
        return static::write($_filePath, $_content, true);
    }

    /**
     * @param string $_filePath
     * @param string $_content
     * @param boolean $_isAppendMode
     * @return integer|bool
     */
    public static function write($_filePath, $_content, $_isAppendMode = false)
    {
        $isNew = !is_file($_filePath);

        if ($isNew) {
            $path = dirname($_filePath);
            if (!is_dir($path)) static::createDir($path);
        }

        $bytes = file_put_contents(
            $_filePath,
            $_content,
            $_isAppendMode ? FILE_APPEND : null
        );

        if ($bytes === false) {
            return false;

        } else {
            if ($isNew) static::allowAll($_filePath);
            return $bytes;
        }
    }

    /**
     * @param string $_path
     * @param string $_pathStartsWith
     * @param string $_uriStartsWith
     * @return self|\Ext\Image
     */
    public static function factory($_path,
                                   $_pathStartsWith = null,
                                   $_uriStartsWith = null)
    {
        $class = static::isImageExt(static::computeExt($_path))
               ? '\Ext\Image'
               : get_called_class();

        return new $class($_path, $_pathStartsWith, $_uriStartsWith);
    }

    public function __construct($_path = null,
                                $_pathStartsWith = null,
                                $_uriStartsWith = null)
    {
        if ($_pathStartsWith) {
            $this->setPathStartsWith($_pathStartsWith);
        }

        if ($_uriStartsWith) {
            $this->setUriStartsWith($_uriStartsWith);
        }

        if ($_path) {
            $this->setPath($_path);
        }
    }

    public function setPathStartsWith($_path)
    {
        $this->_pathStartsWith = $_path;
    }

    public function getPathStartsWith()
    {
        return $this->_pathStartsWith;
    }

    public function setUriStartsWith($_uri)
    {
        $this->_uriStartsWith = $_uri;
    }

    public function getUriStartsWith()
    {
        return $this->_uriStartsWith;
    }

    public static function computeUri($_path,
                                      $_pathStart = null,
                                      $_uriStart = null)
    {
        $uriStart = is_null($_uriStart) ? '/' : $_uriStart;

        if ($_pathStart) {
            $pathStart = $_pathStart;

        } else if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $pathStart = $_SERVER['DOCUMENT_ROOT'];

        } else {
            throw new \Exception('Don\'t know there is public folder.');
        }

        if (substr($uriStart, strlen($uriStart) - 1) == '/') {
            $pathStart = rtrim($pathStart, '/') . '/';
        }

        return str_replace($pathStart, $uriStart, $_path);
    }

    public function setPath($_path)
    {
        $this->_path = $_path;
        $this->_dir = dirname($this->_path);
        $this->_filename = basename($this->_path);
        $this->_ext = static::computeExt($this->_path);
        $this->_name = static::computeName($this->_filename);

        $this->setUri(static::computeUri(
            $this->_path,
            $this->getPathStartsWith(),
            $this->getUriStartsWith()
        ));
    }

    public function delete()
    {
        static::deleteFile($this->getPath());

        if (static::isDirEmpty($this->getDir())) {
            static::deleteDir($this->getDir(), true);
        }
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getDir()
    {
        return $this->_dir;
    }

    public function getUri()
    {
        return $this->_uri;
    }

    public function getUrl()
    {
        return static::concatUrl($this->getUri());
    }

    public function setUri($_uri)
    {
        $this->_uri = $_uri;
    }

    public function getFilename()
    {
        return $this->_filename;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getExt()
    {
        return $this->_ext;
    }

    public static function isImageExt($_ext)
    {
        return in_array(
            strtolower($_ext),
            array('gif', 'jpeg', 'jpg', 'png', 'tiff')
        );
    }

    public function isImage()
    {
//        return static::isImageExt($this->getExt());
        return strpos($this->getMime(), 'image') !== false;
    }

    public function getMime()
    {
        if (!isset($this->_mime)) {
//            $this->_mime = 'application/' . $this->getExt();
            $this->_mime = static::computeMime($this->getPath());
        }

        return $this->_mime;
    }

    public static function computeMime($_filePath)
    {
        return mime_content_type($_filePath);
    }

    public function getSize()
    {
        if (is_null($this->_size)) {
            $this->_size = is_file($this->getPath())
                         ? filesize($this->getPath())
                         : false;
        }

        return $this->_size;
    }

    public function setSize($_size)
    {
        $this->_size = (int) $_size;
    }

    public function getSizeMeasure()
    {
        return static::computeSizeMeasure($this->getSize());
    }

    public function getXml($_node = null, $_xml = null, $_attrs = null)
    {
        $attrs = array(
            'uri' => $this->getUri(),
            'path' => $this->getPath(),
            'filename' => $this->getFilename(),
            'name' => $this->getName(),
            'extension' => $this->getExt()
        );

        if ($_attrs) {
            $attrs = array_merge($attrs, $_attrs);
        }

        $xml = is_array($_xml) ? $_xml : array($_xml);
        $size = $this->getSizeMeasure();

        $xml[] = Xml::cdata('size', $size['string'], array(
            'xml:lang' => 'ru',
            'value' => $size['value'],
            'measure' => $size['measure']
        ));

        foreach (self::$_langs as $lang) {
            if (isset($size["string-$lang"])) {
                $xml[] = Xml::cdata('size', $size["string-$lang"], array(
                    'xml:lang' => $lang,
                    'value' => $size['value'],
                    'measure' => $size["measure-$lang"]
                ));
            }
        }

        return Xml::node(empty($_node) ? 'file' : $_node, $xml, $attrs);
    }

    /**
     * @param \DOMDocument $_dom
     * @param string $_name
     * @param array $_attrs
     * @return \DOMElement
     */
    public function getNode($_dom, $_name = null, $_attrs = null)
    {
        $size = $this->getSizeMeasure();
        $node = $_dom->createElement(empty($_name) ? 'file' : $_name);

        if (!empty($_attrs)) {
            foreach ($_attrs as $name => $value) {
                $node->setAttribute(Xml::normalize($name), $value);
            }
        }

        $node->setAttribute('uri', $this->getUri());
        $node->setAttribute('path', $this->getPath());
        $node->setAttribute('filename', $this->getFilename());
        $node->setAttribute('name', $this->getName());
        $node->setAttribute('extension', $this->getExt());

        $s = $_dom->createElement('size');
        $s->setAttribute('xml:lang', 'ru');
        $s->setAttribute('value', $size['value']);
        $s->setAttribute('measure', $size['measure']);
        $s->appendChild($_dom->createCDATASection($size['string']));
        $node->appendChild($s);

        foreach (self::$_langs as $lang) {
            if (isset($size["string-$lang"])) {
                $s = $_dom->createElement('size');
                $node->appendChild($s);
                $s->setAttribute('xml:lang', $lang);
                $s->setAttribute('value', $size['value']);
                $s->setAttribute('measure', $size["measure-$lang"]);
                $s->appendChild(
                    $_dom->createCDATASection($size["string-$lang"])
                );
            }
        }

        return $node;
    }

    public static function allowAll($_path)
    {
        return static::chmod($_path, 0777);
    }

    public static function chmod($_path, $_mode)
    {
        return is_file($_path) ? @chmod($_path, $_mode) : false;
    }

    /**
     * @param string $_url
     * @return array
     */
    public static function parseUrl($_url = null)
    {
        if (is_null($_url) && isset(static::$_url)) {
            return static::$_url;
        }

        $serverRequestUri = empty($_SERVER['REQUEST_URI'])
                          ? null
                          : $_SERVER['REQUEST_URI'];

        if (is_null($_url)) {
            $url = $serverRequestUri ?: '/';

//            if (!empty($_SERVER['QUERY_STRING'])) {
//                $url .= '?' . $_SERVER['QUERY_STRING'];
//            }

        } else {
            $url = $_url;
        }

        // Если нет закрывающего слэша и последний элемент
        // не файл (есть расширение), то путь будет определен не правильно.
        // Поэтому в таких случаях дописывается слэш.

        $tmp = explode('?', $url);

        if (!static::computeExt($tmp[0])) {
            $tmp[0] = rtrim($tmp[0], '/') .  '/';
        }

        $url = implode('?', $tmp);
        $result = parse_url($url);

        $result['request_uri'] = $serverRequestUri;

        if (!isset($result['path'])) {
            $result['path'] = '/';
        }

        if (!isset($result['query'])) {
            $result['query'] = '';
        }

        if (is_null($_url)) {
            static::$_url = $result;
        }

        return $result;
    }

    public static function concatUrl($_uri = null, $_host = null)
    {
        $url = static::parseUrl($_uri);

        if (empty($url['host'])) {
            if (!empty($_host)) {
                $url['host'] = $_host;

            } else if (!empty($_SERVER['HTTP_HOST'])) {
                $url['host'] = $_SERVER['HTTP_HOST'];
            }
        }

        $uri = '/' . ltrim($url['path'], '/');

        if (!empty($url['query'])) {
            $uri .= '?' . $url['query'];
        }

        return empty($url['host']) ? $uri : "{$url['host']}$uri";
    }

    public static function computeDirStructure($_value)
    {
        return implode(
            DIRECTORY_SEPARATOR,
            array_slice(str_split(md5($_value), 1), 0, 4)
        );
    }

    /**
     * @return int
     */
    public static function getUploadMaxFilesize()
    {
        $size = trim(ini_get('upload_max_filesize'));
        $last = strtolower($size[strlen($size) - 1]);
        $bites = (int) $size;

        switch ($last) {
            case 'g': $bites *= 1024;
            case 'm': $bites *= 1024;
            case 'k': $bites *= 1024;
        }

        return $bites;
    }
}
