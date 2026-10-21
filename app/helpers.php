<?php

use Illuminate\Support\Str;

if (! function_exists('notification_relative_url')) {
    /**
     * Return a relative URL (path + query) from a stored notification link.
     *
     * Existing notifications may store an absolute URL (e.g. http://localhost/...).
     * New notifications store a relative URL (route(..., absolute:false)).
     * This helper normalises both so the bell/index views always produce a
     * host-relative href that matches the current request scheme + host.
     */
    function notification_relative_url(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        // Already relative (starts with '/' but not '//').
        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
            return $url;
        }

        // Protocol-relative URL (//host/path) -> strip scheme.
        if (Str::startsWith($url, '//')) {
            return Str::after($url, '//');
        }

        // Absolute URL: strip scheme + host, keep path + query + fragment.
        $parsed = parse_url($url);
        if ($parsed === false) {
            return $url;
        }

        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $path . $query . $fragment;
    }
}