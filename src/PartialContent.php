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
        $ranges = $this->getRanges();

        if ($ranges === false) {
            return false;
        } elseif (count($ranges) === 0) {
            $this->headers->setStatus(416, 'Requested Range Not Satisfiable');
            $this->headers->setHeader('Content-Range', sprintf('bytes */%d', $this->response->getLength()));
            return true;
        }

        if ($this->multi === false) {
            $this->sendSinglePart($ranges[0][0], $ranges[0][1]);
        } else {
            $this->sendMultiPart($ranges);
        }

        return true;
    }

    private function getRanges()
    {
        if (!isset($this->headers['range'])) {
            return false;
        } elseif (!preg_match('/^bytes=([\r\n\t ]*\d*-\d*)(?:[\r\n\t ]*,(?1))*$/', $this->headers['range'])) {
            return false;
        }

        preg_match_all('/(\d*)-(\d*)/', $this->headers['range'], $matches);

        if (array_search('-', $matches[0], true) !== false) {
            return false;
        }

        return $this->getValidRanges(array_map(null, $matches[1], $matches[2]));
    }

    private function getValidRanges($parsedRanges)
    {
        $ranges = [];
        $length = $this->response->getLength();
        $this->multi = count($parsedRanges) > 1;

        foreach ($parsedRanges as $range) {
            $valid = $this->validateRange($range[0], $range[1], $length);

            if ($valid === false) {
                return false;
            } elseif ($valid[0] > $valid[1]) {
                continue;
            }

            $ranges[] = $valid;
        }

        return $ranges;
    }

    private function validateRange($min, $max, $length)
    {
        if (strlen($min) === 0) {
            return [max(0, $length - 1 - (int) $max), $length - 1];
        } elseif (strlen($max) === 0) {
            return [(int) $min, $length - 1];
        }

        if ((int) $min > (int) $max) {
            return false;
        }

        return [(int) $min, min((int) $max, $length - 1)];
    }

    private function sendSinglePart($start, $end)
    {
        $this->headers->setStatus(206, 'Partial Content');
        $this->headers->setHeaders([
            'Accept-Ranges' => 'bytes',
            'Content-Type' => $this->response->getType(),
            'Content-Length' => $end - $start + 1,
            'Content-Range' => sprintf('bytes %d-%d/%d', $start, $end, $this->response->getLength()),
        ]);

        $this->headers->setCacheHeaders(
            $this->response->getLastModified(),
            $this->response->getETag(),
            $this->response->getMaxAge()
        );

        $this->response->open();
        $this->response->outputBytes($start, $end);
        $this->response->close();
    }

    private function sendMultiPart(array $ranges)
    {
        $boundary = $this->generateBoundary();
        $separator = sprintf(
            "\r\n--%s\r\nContent-Type: %s\r\nContent-Range: bytes %%d-%%d/%d\r\n\r\n",
            $boundary,
            $this->response->getType(),
            $this->response->getLength()
        );

        $ranges = $this->coalesceRanges($ranges, strlen($separator));
        $separators = array_map(function ($range) use ($separator) {
            return sprintf($separator, $range[0], $range[1]);
        }, $ranges);

        $this->sendMultiResponse($ranges, $separators, $boundary);
    }

    private function sendMultiResponse(array $ranges, array $separators, $boundary)
    {
        $end = sprintf("\r\n--%s--\r\n", $boundary);
        $length = array_reduce($ranges, function ($total, $range) {
            return $total + ($range[1] - $range[0] + 1);
        }, strlen($end) + array_sum(array_map('strlen', $separators)));

        $this->headers->setStatus(206, 'Partial Content');
        $this->headers->setHeaders([
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'multipart/byteranges; boundary=' . $boundary,
            'Content-Length' => $length,
        ]);

        $this->headers->setCacheHeaders(
            $this->response->getLastModified(),
            $this->response->getETag(),
            $this->response->getMaxAge()
        );

        $this->outputMultiResponse($ranges, $separators, $end);
    }

    private function outputMultiResponse(array $ranges, array $separators, $end)
    {
        $this->response->open();

        foreach ($ranges as $range) {
            echo array_shift($separators);
            $this->response->outputBytes($range[0], $range[1]);
        }

        $this->response->close();

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
                if (!$this->isSeparateRange($range, $scope, $gap)) {
                    $accepted[$key][0] = min($range[0], $scope[0]);
                    $accepted[$key][1] = max($range[1], $scope[1]);
                    continue 2;
                }
            }

            $accepted[] = $range;
        }

        return $accepted;
    }

    private function isSeparateRange($one, $two, $gap)
    {
        return $one[1] < $two[0] - $gap || $one[0] > $two[1] + $gap;
    }
}
