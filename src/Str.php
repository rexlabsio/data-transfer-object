<?php

namespace Rexlabs\DataTransferObject;

/**
 * Class Str
 *
 * A little copy paste is better than a little dependency
 *
 * @package Rexlabs\DataTransferObject
 */
class Str
{
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public static function before($subject, $search): string
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }
}
