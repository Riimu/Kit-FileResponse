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

    public function getResponseStatus($lastModified, $eTag)
    {
        if ($this->checkCache($lastModified, $eTag)) {
            return $this->getCacheStatus();
        } elseif (!$this->checkConditions($lastModified, $eTag)) {
            return self::HTTP_PRECONDITION_FAILED;
        } elseif ($this->checkRange($lastModified, $eTag)) {
            return self::HTTP_PARTIAL_CONTENT;
        }

        return $this->getDefaultStatus();
    }

    private function getDefaultStatus()
    {
        if (isset($this->headers['if-range'])) {
            return self::HTTP_OK;
        }

        return isset($this->headers['range']) ? self::HTTP_PARTIAL_CONTENT : self::HTTP_OK;
    }

    private function getCacheStatus()
    {
        if (isset($this->headers['if-none-match'])) {
            return in_array(strtoupper($this->headers->getRequestMethod()), ['GET', 'HEAD'])
                ? self::HTTP_NOT_MODIFIED : self::HTTP_PRECONDITION_FAILED;
        }

        return self::HTTP_NOT_MODIFIED;
    }

    public function checkCache($lastModified, $eTag)
    {
        return $this->matchConditionals($lastModified, $eTag, 'if-modified-since', 'if-none-match', false);
    }

    public function checkConditions($lastModified, $eTag)
    {
        return $this->matchConditionals($lastModified, $eTag, 'if-unmodified-since', 'if-match', true);
    }

    private function matchConditionals($lastModified, $eTag, $timeHeader, $tagHeader, $default)
    {
        if ($this->hasHeaderConflict()) {
            throw new UndefinedResultException('Undefined combination of conditional headers');
        } elseif (isset($this->headers[$timeHeader]) || isset($this->headers[$tagHeader])) {
            return $this->matchConditionalHeaders($lastModified, $eTag, $timeHeader, $tagHeader);
        }

        return $default;
    }

    private function hasHeaderConflict()
    {
        $cacheHeaders = isset($this->headers['if-modified-since']) || isset($this->headers['if-none-match']);
        $conditionHeaders = isset($this->headers['if-unmodified-since']) || isset($this->headers['if-match']);

        return $cacheHeaders && $conditionHeaders;
    }

    private function matchConditionalHeaders($lastModified, $eTag, $timeHeader, $tagHeader)
    {
        $notModified = !(isset($this->headers[$timeHeader]) &&
            $this->modifiedSince($lastModified, $this->headers[$timeHeader]));
        $match = !(isset($this->headers[$tagHeader]) &&
            !$this->matchETag($eTag, $this->headers[$tagHeader]));

        return $match && $notModified;
    }

    public function checkRange($lastModified, $eTag)
    {
        if (!isset($this->headers['range'], $this->headers['if-range'])) {
            return false;
        }

        if (preg_match('/^(?:W\\/)?"(?:[^"\\\\]+|\\\\.)++"$/', $this->headers['if-range'])) {
            return $this->matchETag($eTag, $this->headers['if-range']);
        }

        return !$this->modifiedSince($lastModified, $this->headers['if-range']);
    }

    private function modifiedSince($lastModified, $headerValue)
    {
        $lastModified = $this->castTimestamp($lastModified);

        if ($lastModified === 0) {
            return true;
        }

        $cache = strtotime($headerValue);
        return $cache === false || $lastModified > $cache;
    }

    private function matchETag($eTag, $set)
    {
        $eTag = (string) $eTag;

        if ($eTag === '') {
            return false;
        } elseif (!preg_match('/^[\r\n\t ]*((?:W\\/)?"(?:[^"\\\\]+|\\\\.)++")([\r\n\t ]*,[\r\n\t ]*(?1))*$/', $set)) {
            return $set === '*';
        }

        preg_match_all('/(W\\/)?"((?:[^"\\\\]+|\\\\.)++)"/', $set, $matches, PREG_SET_ORDER);
        return $this->tagMatches($eTag, $matches);
    }

    private function tagMatches($tag, $matches)
    {
        foreach ($matches as $match) {
            if ($match[1] === 'W/') {
                continue;
            } elseif (stripslashes($match[2]) === $tag) {
                return true;
            }
        }

        return false;
    }

    private function castTimestamp($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            return $timestamp->getTimestamp();
        }

        return version_compare(PHP_VERSION, '5.5', '>=') && $timestamp instanceof \DateTimeInterface
            ? $timestamp->getTimestamp() : (int) $timestamp;
    }
}
