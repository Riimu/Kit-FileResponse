<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class StringResponse extends AbstractResponse
{
    private $string;

    public function __construct($string, $filename = null)
    {
        $this->string = $string;
        $this->setName($filename);
    }

    public function getETag()
    {
        $eTag = parent::getETag();

        if ($eTag === null) {
            $eTag = md5($this->string);
        }

        return $eTag;
    }

    public function getLength()
    {
        return strlen($this->string);
    }

    public function output()
    {
        echo $this->string;
    }

    public function outputBytes($start, $end)
    {
        echo substr($this->string, $start, $end - $start + 1);
    }
}
