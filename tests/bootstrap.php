<?php

/**
 * PHPUnit bootstrap for the Events Calendar Plus plugin.
 *
 * These are pure unit tests: no database and no full WordPress bootstrap.
 * The handful of WP functions the exercised code path touches are stubbed
 * below, but only when absent, so the suite also runs unchanged inside a
 * WP-bootstrapped test environment.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (! function_exists('wp_timezone')) {
    function wp_timezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}

if (! function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        $map = [
            'timezone_string' => 'UTC',
            'date_format'     => 'Y-m-d',
            'time_format'     => 'H:i',
        ];
        return $map[$option] ?? $default;
    }
}

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (! function_exists('esc_html__')) {
    function esc_html__($text, $domain = null)
    {
        return $text;
    }
}

EventEspresso\CalendarPlus\api\DateTimeHelper::initialize();
