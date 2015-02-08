<?php

namespace Riimu\Kit\FileResponse;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class HeaderMatch
{
    public function modifiedSince($lastModified, $headerValue)
    {
        $lastModified = self::castTimestamp($lastModified);

        if ($lastModified === 0) {
            return true;
        }

        $cache = strtotime($headerValue);
        return $cache === false || $lastModified > $cache;
    }

    public function matchETag($eTag, $headerValue)
    {
        $eTag = (string) $eTag;

        if ($eTag === '') {
            return false;
        } elseif (!preg_match(
            '/^[\r\n\t ]*((?:W\\/)?"(?:[^"\\\\]+|\\\\.)++")([\r\n\t ]*,[\r\n\t ]*(?1))*$/',
            $headerValue
        )) {
            return $headerValue === '*';
        }

        preg_match_all('/(W\\/)?"((?:[^"\\\\]+|\\\\.)++)"/', $headerValue, $matches, PREG_SET_ORDER);
        return $this->tagMatches($eTag, $matches);
    }

    private function tagMatches($tag, $matches)
    {
        foreach ($matches as $match) {
            if ($match[1] === 'W/') {
                continue;
            } elseif (stripslashes($match[2]) === $tag) {
                return true;
            }
        }

        return false;
    }

    public static function castTimestamp($timestamp)
    {
        if ($timestamp instanceof \DateTime) {
            return $timestamp->getTimestamp();
        }

        return version_compare(PHP_VERSION, '5.5', '>=') && $timestamp instanceof \DateTimeInterface
            ? $timestamp->getTimestamp() : (int) $timestamp;
    }
}
