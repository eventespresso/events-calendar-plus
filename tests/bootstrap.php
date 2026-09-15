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

/*
 * Minimal fakes for the WP scripts/styles registry and hooks API, used by
 * AssetsRegistrationTest. Faithful enough to model the one behaviour under test:
 * creating WP_Scripts/WP_Styles fires wp_default_scripts/wp_default_styles once.
 */

if (! isset($GLOBALS['wp_test_actions'])) {
    $GLOBALS['wp_test_actions']     = [];
    $GLOBALS['wp_test_did_actions'] = [];
}

if (! function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1): bool
    {
        $GLOBALS['wp_test_actions'][$hook][] = $callback;
        return true;
    }
}

if (! function_exists('remove_action')) {
    function remove_action($hook, $callback, $priority = 10): bool
    {
        foreach ($GLOBALS['wp_test_actions'][$hook] ?? [] as $index => $registered) {
            if ($registered === $callback) {
                unset($GLOBALS['wp_test_actions'][$hook][$index]);
            }
        }
        return true;
    }
}

if (! function_exists('do_action_ref_array')) {
    function do_action_ref_array($hook, array $args): void
    {
        $GLOBALS['wp_test_did_actions'][$hook] = ($GLOBALS['wp_test_did_actions'][$hook] ?? 0) + 1;
        foreach ($GLOBALS['wp_test_actions'][$hook] ?? [] as $callback) {
            $callback(...$args);
        }
    }
}

if (! function_exists('did_action')) {
    function did_action($hook): int
    {
        return $GLOBALS['wp_test_did_actions'][$hook] ?? 0;
    }
}

if (! class_exists('_WP_Dependency')) {
    class _WP_Dependency
    {
        public $handle;
        public $src;
        public array $deps = [];
        public $ver = false;
        public $args = null;
        public array $extra = [];

        public function __construct($handle = '', $src = '', $deps = [], $ver = false, $args = null)
        {
            $this->handle = $handle;
            $this->src    = $src;
            $this->deps   = (array) $deps;
            $this->ver    = $ver;
            $this->args   = $args;
        }

        public function add_data($name, $data): bool
        {
            $this->extra[$name] = $data;
            return true;
        }
    }
}

if (! class_exists('WP_Dependencies')) {
    class WP_Dependencies
    {
        public array $registered = [];

        public function add($handle, $src, $deps = [], $ver = false, $args = null): bool
        {
            $this->registered[$handle] = new _WP_Dependency($handle, $src, $deps, $ver, $args);
            return true;
        }

        public function query($handle, $list = 'registered')
        {
            return $this->registered[$handle] ?? false;
        }
    }
}

if (! class_exists('WP_Scripts')) {
    class WP_Scripts extends WP_Dependencies
    {
    }
}

if (! class_exists('WP_Styles')) {
    class WP_Styles extends WP_Dependencies
    {
    }
}

if (! function_exists('wp_scripts')) {
    function wp_scripts(): WP_Scripts
    {
        if (! ($GLOBALS['wp_scripts'] ?? null) instanceof WP_Scripts) {
            $GLOBALS['wp_scripts'] = new WP_Scripts();
            do_action_ref_array('wp_default_scripts', [$GLOBALS['wp_scripts']]);
        }
        return $GLOBALS['wp_scripts'];
    }
}

if (! function_exists('wp_styles')) {
    function wp_styles(): WP_Styles
    {
        if (! ($GLOBALS['wp_styles'] ?? null) instanceof WP_Styles) {
            $GLOBALS['wp_styles'] = new WP_Styles();
            do_action_ref_array('wp_default_styles', [$GLOBALS['wp_styles']]);
        }
        return $GLOBALS['wp_styles'];
    }
}

if (! function_exists('wp_script_is')) {
    function wp_script_is($handle, $status = 'enqueued'): bool
    {
        return (bool) wp_scripts()->query($handle, $status);
    }
}

if (! function_exists('wp_style_is')) {
    function wp_style_is($handle, $status = 'enqueued'): bool
    {
        return (bool) wp_styles()->query($handle, $status);
    }
}

if (! function_exists('wp_json_file_decode')) {
    function wp_json_file_decode($filename, $options = [])
    {
        return json_decode(file_get_contents($filename), ! empty($options['associative']));
    }
}

if (! function_exists('wp_get_environment_type')) {
    function wp_get_environment_type(): string
    {
        return 'production';
    }
}

if (! function_exists('esc_url_raw')) {
    function esc_url_raw($url)
    {
        return $url;
    }
}

if (! defined('EVENTS_CALENDAR_PLUS_BASE_PATH')) {
    define('EVENTS_CALENDAR_PLUS_BASE_PATH', dirname(__DIR__) . '/');
}

if (! defined('EVENTS_CALENDAR_PLUS_BASE_URL')) {
    define('EVENTS_CALENDAR_PLUS_BASE_URL', 'https://example.test/wp-content/plugins/events-calendar-plus/');
}

EventEspresso\CalendarPlus\api\DateTimeHelper::initialize();
