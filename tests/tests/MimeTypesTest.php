<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MimeTypesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMimeType()
    {
        $this->assertSame('text/plain', MimeTypes::getMimeType('txt'));
    }

    public function testMissingMimeType()
    {
        $this->assertFalse(MimeTypes::getMimeType('NoSuchExtension'));
    }

    public function testGetExtensions()
    {
        $this->assertSame(['jpe', 'jpeg', 'jpg'], MimeTypes::getExtensions('image/jpeg'));
    }
}
