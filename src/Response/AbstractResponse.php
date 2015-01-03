<?php

namespace Riimu\Kit\FileResponse\Response;
use Riimu\Kit\FileResponse\MimeTypes;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractResponse implements Response
{
    protected $name;
    protected $type;
    protected $lastModified;
    protected $eTag;
    protected $maxAge;

    public function setName($filename)
    {
        $this->name = $filename === null ? null : (string) $filename;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($mimeType)
    {
        $this->type = $mimeType === null ? null : (string) $mimeType;
    }

    public function getType()
    {
        if (!isset($this->type)) {
            if ($name = $this->getName()) {
                $extension = pathinfo($name, PATHINFO_EXTENSION);

                if ($extension && $type = MimeTypes::getMimeType($extension)) {
                    return $type;
                }
            }

            return 'application/octet-stream';
        }

        return $this->type;
    }

    public function setLastModified($timestamp)
    {
        $this->lastModified = $timestamp === null
            ? null : (is_object($timestamp) ? (int) $timestamp->getTimestamp() : (int) $timestamp);
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setETag($eTag)
    {
        $this->eTag = $eTag === null ? null : (string) $eTag;
    }

    public function getETag()
    {
        return $this->eTag;
    }

    public function setExpires($timestamp)
    {
        $timestamp = $timestamp === null
            ? null : (is_object($timestamp) ? (int) $timestamp->getTimestamp() : (int) $timestamp);
        $this->setMaxAge($timestamp === null ? null : $timestamp - time());
    }

    public function setMaxAge($seconds)
    {
        $this->maxAge = $seconds === null ? null : max(0, (int) $seconds);
    }

    public function getMaxAge()
    {
        return $this->maxAge;
    }
}
