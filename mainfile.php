<?php
/**
 * Plugin Name: Events Calendar Plus
 * Plugin URI:  https://www.eventespresso.com
 * Description: Events Calendar Plus (Calendar+) is the Universal Events Calendar for WordPress - display ALL the events!
 * Version:     1.0.14
 * Author:      Event Espresso
 * Author URI:  https://www.eventespresso.com/
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: events-calendar-plus
 * Domain Path: /src/languages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die;
}

/*
 * The unique identifier of this plugin.
 */
const EVENTS_CALENDAR_PLUS_SLUG = 'events-calendar-plus';

/**
 * The current version of the plugin. Uses semantic versioning.
 */
const EVENTS_CALENDAR_PLUS_VERSION = '1.0.14';

const EVENTS_CALENDAR_PLUS_MIN_PHP_VERSION = '7.4';

const EVENTS_CALENDAR_PLUS_MIN_WP_VERSION = '6.8';

if (version_compare(PHP_VERSION, EVENTS_CALENDAR_PLUS_MIN_PHP_VERSION, '>=')) {
    define('EVENTS_CALENDAR_PLUS_BASE_PATH', plugin_dir_path(__FILE__));
    define('EVENTS_CALENDAR_PLUS_BASE_URL', plugin_dir_url(__FILE__));

    // composer autoloader
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/src/bootstrap.php';

    global $wp_version;
    if (version_compare($wp_version, EVENTS_CALENDAR_PLUS_MIN_WP_VERSION, '<')) {
        // load polyfills
        require __DIR__ . '/src/tools/compatibility.php';
    }

    add_action('init', 'loadEventsCalendarPlus', 1);

    register_activation_hook(
        __FILE__,
        ['EventEspresso\CalendarPlus\PluginActivation', 'activate']
    );

    register_deactivation_hook(
        __FILE__,
        ['EventEspresso\CalendarPlus\PluginActivation', 'deactivate']
    );
} else {
    add_action('admin_notices', 'eventsCalendarPlusMinPhpError');
}
