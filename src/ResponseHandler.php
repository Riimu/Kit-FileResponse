<?php

namespace Riimu\Kit\FileResponse;

use Riimu\Kit\FileResponse\Response\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ResponseHandler
{
    private $headers;

    public function __construct(Headers $headers = null)
    {
        $this->headers = $headers === null ? new Headers() : $headers;
    }

    public function send(Response $response, $attach = true)
    {
        if (headers_sent()) {
            throw new \RuntimeException('Cannot create response, headers already sent');
        }

        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }

        ini_set('zlib.output_compression', 0);

        $lastModified = $response->getLastModified();
        $eTag = $response->getETag();

        if (!$lastModified && !$eTag) {
            return isset($this->headers['range'])
                ? $this->sendPartial($response) : $this->sendNormal($response, $attach);
        }

        switch ((new ConditionalGet($this->headers))->checkStatus($lastModified, $eTag)) {
            case ConditionalGet::HTTP_OK:
                return $this->sendNormal($response, $attach);
            case ConditionalGet::HTTP_PARTIAL_CONTENT:
                return $this->sendPartial($response);
            case ConditionalGet::HTTP_NOT_MODIFIED:
                return $this->sendNotModified($response);
            case ConditionalGet::HTTP_PRECONDITION_FAILED:
                return $this->sendPreconditionFailed();
            default:
                throw new \RuntimeException("Unexpected conditional get status");
        }
    }

    private function sendNormal(Response $response, $attach)
    {
        $this->headers->setStatus(200, 'OK');
        $this->headers->setHeader('Accept-Ranges', 'bytes');
        $this->headers->setHeader('Content-Type', $response->getType());
        $this->headers->setHeader('Content-Length', $response->getLength());

        if ($attach && $name = $response->getName()) {
            $this->headers->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', addslashes($name)));
        }

        $this->headers->setCacheHeaders($response->getLastModified(), $response->getETag(), $response->getMaxAge());
        $response->output();

        return true;
    }

    private function sendPartial(Response $response)
    {
        $partial = new PartialContent($response, $this->headers);
        return $partial->send() ? true : $this->sendNormal($response, false);
    }

    private function sendNotModified(Response $response)
    {
        $this->headers->setStatus(304, 'Not Modified');
        $this->headers->setCacheHeaders($response->getLastModified(), $response->getETag(), $response->getMaxAge());
        return true;
    }

    private function sendPreconditionFailed()
    {
        $this->headers->setStatus(412, 'Precondition Failed');
        return true;
    }
}
