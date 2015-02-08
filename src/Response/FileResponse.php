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
    private $handle;

    public function __construct($filename)
    {
        if (!file_exists($filename) || !is_readable($filename) || is_dir($filename)) {
            throw new \InvalidArgumentException('File does not exist or it is not a readable file');
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

        if ($name === null) {
            $name = basename($this->path);
        }

        return $name;
    }

    public function getLastModified()
    {
        $lastModified = parent::getLastModified();

        if ($lastModified === null) {
            $lastModified = filemtime($this->path);
        }

        return $lastModified;
    }

    public function getETag()
    {
        $eTag = parent::getETag();

        if ($eTag === null) {
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
        if (readfile($this->path) === false) {
            throw new \RuntimeException('Error occurred while reading output file');
        };
    }

    public function open()
    {
        $this->handle = fopen($this->path, 'rb');

        if ($this->handle === false) {
            throw new \RuntimeException('Error occurred while opening output file');
        }
    }

    public function outputBytes($start, $end)
    {
        fseek($this->handle, $start);

        for ($bytes = $end - $start + 1; $bytes > 0; $bytes -= strlen($output)) {
            $output = fread($this->handle, min(8192, $bytes));

            if ($output === false) {
                throw new \RuntimeException('Error occurred while attempting to read the output file');
            } elseif ($this->isUnexpectedEof($bytes - strlen($output))) {
                throw new \RuntimeException('Unexpected end of output file');
            }

            echo substr($output, 0, $bytes);
        }
    }

    private function isUnexpectedEof($bytesLeft)
    {
        return feof($this->handle) && $bytesLeft > 0;
    }

    public function close()
    {
        if (fclose($this->handle) === false) {
            throw new \RuntimeException('Error occurred while closing output file');
        }

        $this->handle = null;
    }
}
