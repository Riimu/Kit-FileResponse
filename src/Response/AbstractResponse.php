<?php

namespace Riimu\Kit\FileResponse\Response;
use Riimu\Kit\FileResponse\HeaderMatch;
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

    private function setProperty($property, $value, $string)
    {
        if ($value === null) {
            $this->$property = null;
        } else {
            $this->$property = $string ? (string) $value : (int) $value;
        }
    }

    public function setName($filename)
    {
        $this->setProperty('name', $filename, true);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($mimeType)
    {
        $this->setProperty('type', $mimeType, true);
    }

    public function getType()
    {
        if (!isset($this->type)) {
            $type = MimeTypes::getMimeType(pathinfo((string) $this->getName(), PATHINFO_EXTENSION));
            return $type === false ? 'application/octet-stream' : $type;
        }

        return $this->type;
    }

    public function setLastModified($timestamp)
    {
        $timestamp = $timestamp === null ? null : HeaderMatch::castTimestamp($timestamp);
        $this->setProperty('lastModified', $timestamp, false);
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setETag($eTag)
    {
        $this->setProperty('eTag', $eTag, true);
    }

    public function getETag()
    {
        return $this->eTag;
    }

    public function setExpires($timestamp)
    {
        if ($timestamp === null) {
            $this->setMaxAge(null);
        } else {
            $this->setMaxAge(HeaderMatch::castTimestamp($timestamp) - time());
        }
    }

    public function setMaxAge($seconds)
    {
        $this->setProperty('maxAge', $seconds, false);
    }

    public function getMaxAge()
    {
        return $this->maxAge;
    }

    public function open()
    {

    }

    public function close()
    {

    }
}
