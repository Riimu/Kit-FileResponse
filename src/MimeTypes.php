<?php

namespace Riimu\Kit\FileResponse;

/**
 * @see https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MimeTypes
{
    private static $types;

    private static function loadList()
    {
        if (!isset(self::$types)) {
            self::$types = require __DIR__ . '/mime_type_list.php';
        }
    }

    public static function getMimeType($extension)
    {
        self::loadList();

        if (!isset(self::$types[$extension])) {
            return false;
        }

        return self::$types[$extension];
    }

    public static function getExtensions($mimeType)
    {
        self::loadList();
        return array_keys(self::$types, $mimeType, true);
    }
}
