<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FileResponse extends AbstractResponse
{
    private $path;
    private $md5;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException('Response file does not exist');
        }

        $this->path = $filename;
        $this->md5 = false;
    }

    public function enableMd5Tag($enable = true)
    {
        $this->md5 = (bool) $enable;
    }

    public function getName()
    {
        $name = parent::getName();

        if (!isset($name)) {
            $name = basename($this->path);
        }

        return $name;
    }

    public function getLastModified()
    {
        $lastModified = parent::getLastModified();

        if (!isset($lastModified)) {
            $lastModified = filemtime($this->path);
        }

        return $lastModified;
    }

    public function getETag()
    {
        $eTag = parent::getETag();

        if (!isset($eTag)) {
            if ($this->md5) {
                $eTag = md5_file($this->path);
            } else {
                $eTag = sprintf('%x-%x', filesize($this->path), filemtime($this->path));
            }
        }

        return $eTag;
    }

    public function getLength()
    {
        return filesize($this->path);
    }

    public function output()
    {
        readfile($this->path);
    }

    public function outputBytes($start, $end)
    {
        $fp = fopen($this->path, 'rb');
        fseek($fp, $start);

        for ($bytes = $end - $start + 1; $bytes > 0 && !feof($fp); $bytes -= strlen($output)) {
            $output = fread($fp, min(8192, $bytes));
            echo substr($output, 0, $bytes);
        }

        fclose($fp);
    }
}
