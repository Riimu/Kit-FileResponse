<?php

define('FILES_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'files');

function defineApacheRequestHeaders()
{
    function apache_request_headers() {
        return ['Host' => 'www.example.com'];
    }
}

require __DIR__ . '/../src/autoload.php';