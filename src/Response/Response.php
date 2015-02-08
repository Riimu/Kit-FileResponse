<?php

namespace Riimu\Kit\FileResponse\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface Response
{
    public function setName($name);
    public function getName();

    public function setType($type);
    public function getType();

    public function setLastModified($timestamp);
    public function getLastModified();

    public function setETag($eTag);
    public function getETag();

    public function setExpires($timestamp);
    public function setMaxAge($seconds);
    public function getMaxAge();

    public function getLength();

    public function output();

    public function open();
    public function outputBytes($start, $end);
    public function close();
}
