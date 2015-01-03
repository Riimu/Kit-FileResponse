<?php

namespace Riimu\Kit\FileResponse;

use Riimu\Kit\FileResponse\Response\Response;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2014, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class PartialContent
{
    private $response;
    private $headers;
    private $multi;

    public function __construct(Response $response, Headers $headers)
    {
        $this->response = $response;
        $this->headers = $headers;
    }

    public function send()
    {
        if (!isset($this->headers['range'])) {
            return false;
        }

        $ranges = $this->parseRanges($this->headers['range']);

        if ($ranges === false) {
            return false;
        } elseif (count($ranges) === 0) {
            $this->headers->setStatus(416, 'Requested Range Not Satisfiable');
            $this->headers->setHeader('Content-Range', sprintf('bytes */%d', $this->response->getLength()));
            return true;
        }

        if ($this->multi === false) {
            $this->sendSingle($ranges[0][0], $ranges[0][1]);
        } else {
            $this->sendMulti($ranges);
        }

        return true;
    }

    private function parseRanges($rangeHeader)
    {
        if (!preg_match('/^bytes=[\r\n\t ]*\d*-\d*(?:[\r\n\t ]*,[\r\n\t ]*\d*-\d*)*$/', $rangeHeader)) {
            return false;
        }

        preg_match_all('/(\d*)-(\d*)/', $rangeHeader, $matches, PREG_SET_ORDER);

        $ranges = [];
        $length = $this->response->getLength();
        $this->multi = count($matches) > 1;

        foreach ($matches as $match) {
            if (strlen($match[1]) === 0 && strlen($match[2]) === 0) {
                return false;
            }

            if (strlen($match[1]) === 0) {
                $start = max(0, $length - 1 - (int) $match[2]);
                $end = $length - 1;
            } elseif (strlen($match[2]) === 0) {
                $start = (int) $match[1];
                $end = $length - 1;
            } else {
                $start = (int) $match[1];
                $end = (int) $match[2];
                if ($start > $end) {
                    return false;
                } elseif ($end >= $length) {
                    $end = $length - 1;
                }
            }

            if ($start > $end) {
                continue;
            }

            $ranges[] = [$start, $end];
        }

        return $ranges;
    }

    private function sendSingle($start, $end)
    {
        $this->headers->setStatus(206, 'Partial Content');
        $this->headers->setHeader('Accept-Ranges', 'bytes');
        $this->headers->setHeader('Content-Type', $this->response->getType());
        $this->headers->setHeader('Content-Length', $end - $start + 1);
        $this->headers->setHeader(
            'Content-Range',
            sprintf('bytes %d-%d/%d', $start, $end, $this->response->getLength())
        );

        $this->headers->setCacheHeaders(
            $this->response->getLastModified(),
            $this->response->getETag(),
            $this->response->getMaxAge()
        );

        $this->response->outputBytes($start, $end);
    }

    private function sendMulti(array $ranges)
    {
        $boundary = $this->generateBoundary();
        $separator = "\r\n--$boundary\r\n" .
            "Content-Type: " . $this->response->getType() . "\r\n" .
            "Content-Range: bytes %d-%d/" . $this->response->getLength() . "\r\n\r\n";

        $ranges = $this->coalesceRanges($ranges, strlen($separator));
        $separators = [];
        $length = 0;

        foreach ($ranges as $key => $range) {
            $formatted = sprintf($separator, $range[0], $range[1]);
            $separators[$key] = $formatted;
            $length += strlen($formatted) + ($range[1] - $range[0] + 1);
        }

        $end = "\r\n--$boundary--\r\n";
        $length += strlen($end);

        $this->headers->setStatus(206, 'Partial Content');
        $this->headers->setHeader('Accept-Ranges', 'bytes');
        $this->headers->setHeader('Content-Type', 'multipart/byteranges; boundary=' . $boundary);
        $this->headers->setHeader('Content-Length', $length);

        $this->headers->setCacheHeaders(
            $this->response->getLastModified(),
            $this->response->getETag(),
            $this->response->getMaxAge()
        );

        foreach ($ranges as $key => $range) {
            echo $separators[$key];
            $this->response->outputBytes($range[0], $range[1]);
        }

        echo $end;
    }

    private function generateBoundary()
    {
        $chars = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'));
        $boundary = 'b';

        for ($i = 0; $i < 31; $i++) {
            $boundary .= $chars[mt_rand(0, count($chars) - 1)];
        }

        return $boundary;
    }

    private function coalesceRanges(array $ranges, $gap)
    {
        $accepted = [];

        foreach ($ranges as $range) {
            foreach ($accepted as $key => $scope) {
                if (!(
                    ($range[0] < $scope[0] && $range[1] < $scope[0] - $gap) ||
                    ($range[1] > $scope[1] && $range[0] > $scope[1] + $gap)
                )) {
                    $accepted[$key][0] = min($range[0], $scope[0]);
                    $accepted[$key][1] = max($range[1], $scope[1]);
                    continue 2;
                }
            }

            $accepted[] = $range;
        }

        return $accepted;
    }
}
