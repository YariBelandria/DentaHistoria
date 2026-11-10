<?php

/**
 * Compatibilidad mínima para entornos sin la extensión mbstring.
 *
 * InfinityFree y la mayoría de hostings incluyen mbstring, pero estos
 * respaldos evitan errores fatales si la extensión no está disponible.
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen($value, ?string $encoding = null): int
    {
        $count = preg_match_all('/./us', (string) $value, $matches);
        return $count === false ? strlen((string) $value) : $count;
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($value, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $chars = preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return substr((string) $value, $start, $length ?? PHP_INT_MAX);
        }
        $slice = $length === null ? array_slice($chars, $start) : array_slice($chars, $start, $length);
        return implode('', $slice);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($value, ?string $encoding = null): string
    {
        return strtoupper((string) $value);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($value, ?string $encoding = null): string
    {
        return strtolower((string) $value);
    }
}

if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, int $offset = 0, ?string $encoding = null)
    {
        return strpos((string) $haystack, (string) $needle, $offset);
    }
}
