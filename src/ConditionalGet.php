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
    private $match;

    public function __construct(Headers $headers = null)
    {
        $this->headers = $headers === null ? new Headers() : $headers;
        $this->match = new HeaderMatch();
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
            $this->match->modifiedSince($lastModified, $this->headers[$timeHeader]));
        $match = !(isset($this->headers[$tagHeader]) &&
            !$this->match->matchETag($eTag, $this->headers[$tagHeader]));

        return $match && $notModified;
    }

    public function checkRange($lastModified, $eTag)
    {
        if (!isset($this->headers['range'], $this->headers['if-range'])) {
            return false;
        }

        if (preg_match('/^(?:W\\/)?"(?:[^"\\\\]+|\\\\.)++"$/', $this->headers['if-range'])) {
            return $this->match->matchETag($eTag, $this->headers['if-range']);
        }

        return !$this->match->modifiedSince($lastModified, $this->headers['if-range']);
    }
}
