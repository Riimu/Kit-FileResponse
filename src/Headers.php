<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Headers implements \ArrayAccess
{
    private $headers;

    public function __construct()
    {
        $this->headers = $this->loadHeaders();
    }

    public function getRequestMethod()
    {
        return empty($_SERVER['REQUEST_METHOD']) ? null : strtoupper($_SERVER['REQUEST_METHOD']);
    }

    protected function loadHeaders()
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            }
        }

        return $headers;
    }

    public function offsetExists($offset)
    {
        return isset($this->headers[strtolower($offset)]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->headers[strtolower($offset)])) {
            return null;
        }

        return $this->headers[strtolower($offset)];
    }

    public function offsetSet($offset, $value)
    {
        $this->headers[strtolower($offset)] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[strtolower($offset)]);
    }
}
