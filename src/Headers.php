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

    const HTTP_DATE = 'D, d M Y H:i:s \G\M\T';

    public function __construct()
    {
        $this->headers = $this->loadHeaders();
    }

    /**
     * @param $code
     * @param $descriptor
     * @codeCoverageIgnore
     */
    public function setStatus($code, $descriptor)
    {
        header($_SERVER['SERVER_PROTOCOL'] . " $code $descriptor", true, $code);
    }

    /**
     * @param $header
     * @param $value
     * @codeCoverageIgnore
     */
    public function setHeader($header, $value)
    {
        header(sprintf('%s: %s', $header, $value));
    }

    public function setHeaders(array $headers)
    {
        array_map([$this, 'setHeader'], array_keys($headers), array_values($headers));
    }

    /**
     * Returns the value for the header.
     * @param string $header Case insensitive name of the header
     * @return string|null Value of the header or null if it is not set
     */
    public function getHeader($header)
    {
        $header = strtolower($header);

        if (!isset($this->headers[$header])) {
            return null;
        }

        return $this->headers[$header];
    }

    /**
     * Tells if the response headers have already been sent.
     * @return boolean True if the headers have been sent, false if not
     * @codeCoverageIgnore
     */
    public function headersSent()
    {
        return headers_sent();
    }

    public function setCacheHeaders($lastModified, $eTag, $maxAge = 0)
    {
        $lastModified = (int) $lastModified;
        $eTag = (string) $eTag;
        $maxAge = (int) $maxAge;
        $headers = [];

        if ($maxAge > 0) {
            $headers['Expires'] = gmdate(self::HTTP_DATE, time() + (int)$maxAge);
            $headers['Cache-Control'] = sprintf('public, max-age=%d', (int)$maxAge);
        } elseif ($lastModified === 0 && $eTag === '') {
            $headers['Cache-Control'] = 'private, max-age=0, no-cache, no-store';
            $headers['Pragma'] = 'no-cache';
        } else {
            $headers['Cache-Control'] = 'public, max-age=0, no-cache';
            $headers['Pragma'] = 'no-cache';
        }

        $headers += $this->getConditionalHeaders($lastModified, $eTag);
        $this->setHeaders($headers);
    }

    private function getConditionalHeaders($lastModified, $eTag)
    {
        $headers = [];

        if ($lastModified != 0) {
            $headers['Last-Modified'] = gmdate(self::HTTP_DATE, $lastModified);
        }
        if ($eTag != '') {
            $headers['ETag'] = sprintf('"%s"', addslashes($eTag));
        }

        return $headers;
    }

    public function getRequestMethod()
    {
        return empty($_SERVER['REQUEST_METHOD']) ? null : strtoupper($_SERVER['REQUEST_METHOD']);
    }

    protected function loadHeaders()
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = [];

            foreach ($_SERVER as $key => $value) {
                if (strncasecmp($key, 'http_', 5) === 0) {
                    $headers[str_replace('_', '-', substr($key, 5))] = $value;
                }
            }
        }

        return array_change_key_case($headers, CASE_LOWER);
    }

    public function offsetExists($offset)
    {
        return $this->getHeader($offset) !== null;
    }

    public function offsetGet($offset)
    {
        $header = $this->getHeader($offset);

        if ($header === null) {
            throw new \InvalidArgumentException("Invalid header '$offset'");
        }

        return $header;
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Cannot change request headers');
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Cannot unset request headers');
    }
}
