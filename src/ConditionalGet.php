<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ConditionalGet
{
    const HTTP_OK = 200;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_PRECONDITION_FAILED = 412;

    private $headers;

    public function __construct(Headers $headers = null)
    {
        $this->headers = $headers === null ? new Headers() : $headers;
    }

    public function checkStatus($lastModified, $eTag)
    {
        if ($this->matchCache($lastModified, $eTag)) {
            if (isset($this->headers['if-none-match']) &&
                !in_array($this->headers->getRequestMethod(), ['GET', 'HEAD'])
            ) {
                return self::HTTP_PRECONDITION_FAILED;
            }
            return self::HTTP_NOT_MODIFIED;
        } elseif (!$this->matchPreCondition($lastModified, $eTag)) {
            return self::HTTP_PRECONDITION_FAILED;
        } elseif (isset($this->headers['if-range']) && !$this->matchRange($lastModified, $eTag)) {
            return self::HTTP_OK;
        }

        return isset($this->headers['range'])
            ? self::HTTP_PARTIAL_CONTENT : self::HTTP_OK;
    }

    public function matchCache($lastModified, $eTag)
    {
        if ($lastModified === null && $eTag === null) {
            throw new \InvalidArgumentException('Last modified time and etag cannot both be null');
        } elseif (!isset($this->headers['if-modified-since']) && !isset($this->headers['if-none-match'])) {
            return false;
        } elseif (isset($this->headers['if-unmodified-since']) || isset($this->headers['if-match'])) {
            throw new ResultUndefinedException('Undefined combination of conditional headers');
        }

        if (isset($this->headers['if-modified-since']) &&
            $this->modifiedSince($lastModified, $this->headers['if-modified-since'])
        ) {
            return false;
        } elseif (isset($this->headers['if-none-match']) && !$this->matchETag($eTag, $this->headers['if-none-match'])) {
            return false;
        }

        return true;
    }

    public function matchPreCondition($lastModified, $eTag)
    {
        return $this->matchConditionals($lastModified, $eTag, 'if-unmodified-since', 'if-match');
        
        if ($lastModified === null && $eTag === null) {
            throw new \InvalidArgumentException('Last modified time and etag cannot both be null');
        } elseif (!isset($this->headers['if-unmodified-since']) && !isset($this->headers['if-match'])) {
            return false;
        } elseif (isset($this->headers['if-modified-since']) || isset($this->headers['if-none-match'])) {
            throw new ResultUndefinedException('Undefined combination of conditional headers');
        }

        if (isset($this->headers['if-unmodified-since']) &&
            $this->modifiedSince($lastModified, $this->headers['if-unmodified-since'])
        ) {
            return false;
        } elseif (isset($this->headers['if-match']) && !$this->matchETag($eTag, $this->headers['if-match'])) {
            return false;
        }

        return true;
    }

    private function matchConditionals($lastModified, $eTag, $timeHeader, $tagHeader)
    {
        if ($lastModified === null && $eTag === null) {
            throw new \InvalidArgumentException('Last modified time and etag cannot both be null');
        } elseif (!isset($this->headers[$timeHeader]) && !isset($this->headers[$tagHeader])) {
            return false;
        } elseif (
            (isset($this->headers['if-modified-since']) || isset($this->headers['if-none-match'])) &&
            (isset($this->headers['if-unmodified-since']) || isset($this->headers['if-match']))
        ) {
            throw new ResultUndefinedException('Undefined combination of conditional headers');
        }

        if (isset($this->headers[$timeHeader]) && $this->modifiedSince($lastModified, $this->headers[$timeHeader])) {
            return false;
        } elseif (isset($this->headers[$tagHeader]) && !$this->matchETag($eTag, $this->headers[$tagHeader])) {
            return false;
        }

        return true;
    }

    public function matchRange($lastModified, $eTag)
    {
        if ($lastModified === null && $eTag === null) {
            throw new \InvalidArgumentException('Last modified time and etag cannot both be null');
        } elseif (!isset($this->headers['range']) || !isset($this->headers['if-range'])) {
            return false;
        }

        $header = $this->headers['if-range'];

        if (preg_match('/^(?:W\\/)?"(?:[^"\\\\]+|\\\\.)++"$/', $header)) {
            return $eTag !== null && $this->matchETag($eTag, $header);
        }

        return !$this->modifiedSince($lastModified, $header);
    }

    private function modifiedSince($lastModified, $headerValue)
    {
        if ($lastModified instanceof \DateTime || $lastModified instanceof \DateTimeInterface) {
            $timestamp = $lastModified->getTimestamp();
        } elseif (is_int($lastModified) || ctype_digit($lastModified)) {
            $timestamp = (int) $lastModified;
        } else {
            throw new \InvalidArgumentException('Invalid timestamp value');
        }

        $cache = strtotime($headerValue);
        return $cache === false || $timestamp > $cache;
    }

    private function matchETag($eTag, $set)
    {
        if (!preg_match(
            '/^[\r\n\t ]*(W\\/)?"(?:[^"\\\\]+|\\\\.)++"([\r\n\t ]*,[\r\n\t ]*(W\\/)?"(?:[^"\\\\]+|\\\\.)++")*$/',
            $set
        )) {
            return $set === '*' ? true : false;
        }

        preg_match_all('/(W\/)?"((?:[^"\\\\]+|\\\\.)++)"/', $set, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1] === 'W/') {
                continue;
            }

            if (stripslashes($match[2]) === (string) $eTag) {
                return true;
            }
        }

        return false;
    }
}

class ResultUndefinedException extends \Exception { }
