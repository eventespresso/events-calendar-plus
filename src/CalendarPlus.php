<?php

namespace EventEspresso\CalendarPlus;

use EventEspresso\CalendarPlus\admin\Admin;
use EventEspresso\CalendarPlus\api\CalendarPlusAPI;
use EventEspresso\CalendarPlus\api\CalendarPlusConfig;
use EventEspresso\CalendarPlus\api\DateTimeHelper;
use EventEspresso\CalendarPlus\frontend\EventDataHandler;
use EventEspresso\CalendarPlus\frontend\Frontend;
use EventEspresso\CalendarPlus\frontend\Maintenance;
use EventEspresso\CalendarPlus\migrations\MigrationsAdmin;
use EventEspresso\CalendarPlus\migrations\DatabaseSchema;
use EventEspresso\CalendarPlus\migrations\MigrationStatus;
use EventEspresso\CalendarPlus\tools\Request;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    CalendarPlus
 * @subpackage CalendarPlus/includes
 * @since      1.0.0
 * @author     Event Espresso <support@eventespresso.com>
 */
class CalendarPlus extends CalendarPlusModule
{
    private Request $request;

    private static array $actions_to_skip = [
        'activate',
        'heartbeat',
    ];

    private static array $paths_to_skip = [
        'favicon.ico',
        'wp-cron.php',
    ];


    /**
     * @param Request $request
     * @param string  $plugin_slug The name of this plugin.
     * @param string  $version     The version of this plugin.
     */
    public function __construct(Request $request, string $plugin_slug, string $version)
    {
        parent::__construct($plugin_slug, $version);
        $this->request = $request;
    }


    public function registerHooks(): void
    {
        if (! $this->loadCalendarPlus()) {
            return;
        }
        add_action('init', [$this, 'initialize'], 5);
        add_action('wp_head', [$this, 'printVersion'], 999);
        if (WP_DEBUG) {
            add_action('wp_ajax_events_calendar_plus_reset_migrations', [$this, 'resetMigrations']);
        }
    }


    private function loadCalendarPlus(): bool
    {
        $path = $this->request->path();
        if ($path && in_array($path, CalendarPlus::$paths_to_skip, true)) {
            return false;
        }
        $action = $this->request->queryParam('action');
        if ($action && in_array($action, CalendarPlus::$actions_to_skip, true)) {
            return false;
        }
        return true;
    }


    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function initialize(): void
    {
        DateTimeHelper::initialize();
        if (DatabaseSchema::migrationsAreRequired()) {
            $this->initializeMigrations();
            return;
        }
        $this->initializeRegularRequests();
    }


    /**
     * only runs when DatabaseSchema::migrationsAreRequired() return true
     *
     * @return void
     * @since 1.0.14
     */
    private function initializeMigrations(): void
    {
        MigrationStatus::markMigrationsAsRequired();

        $custom_post = new CalendarPlusPostType(false);
        $custom_post->registerHooks();

        $post_meta = new CalendarPlusPostMeta();
        $post_meta->registerHooks();

        $frontend = new Maintenance($this->pluginSlug(), $this->version());
        $frontend->registerHooks();

        $migrations_admin = new MigrationsAdmin($this->request, $this->pluginSlug(), $this->version());
        $migrations_admin->registerHooks();
    }


    /**
     * runs for all non-migration related requests
     *
     * @return void
     * @since 1.0.14
     */
    private function initializeRegularRequests(): void
    {
        $custom_post = new CalendarPlusPostType();
        $custom_post->registerHooks();

        $post_meta = new CalendarPlusPostMeta();
        $post_meta->registerHooks();

        $blocks = new CalendarPlusBlocks($this->pluginSlug(), $this->version());
        $blocks->registerHooks();

        $config = new CalendarPlusConfig();
        $config->initialize();
        // load production assets
        $assets = new Assets($this->pluginSlug(), $this->version());
        $assets->registerHooks();

        $data_handler = new EventDataHandler();

        $api = new CalendarPlusAPI($config, $data_handler, $this->pluginSlug(), $this->version());
        $api->registerHooks();

        $module = is_admin()
            ? new Admin($config, $data_handler, $this->pluginSlug(), $this->version())
            : new Frontend($config, $data_handler, $this->pluginSlug(), $this->version());
        $module->registerHooks();

        $this->loadAddons($config, $api, $module, $assets, $data_handler, $custom_post, $post_meta, $blocks);
    }


    /**
     * to load an add-on, add something like the following to its mainfile:
     *
     *  add_action( 'EventsCalendarPlusInitialization', 'loadMyCalendarPlusAddon' );
     *
     *  function loadMyCalendarPlusAddon(
     *      EventEspresso\CalendarPlus\tools\Request $request,
     *      EventEspresso\CalendarPlus\api\CalendarPlusConfig $config,
     *      EventEspresso\CalendarPlus\api\CalendarPlusAPI $api,
     *      EventEspresso\CalendarPlus\CalendarPlusModule $module, // Admin OR Frontend module
     *      EventEspresso\CalendarPlus\Assets $assets,
     *      EventEspresso\CalendarPlus\frontend\EventDataHandler $data_handler,
     *      EventEspresso\CalendarPlus\CalendarPlusPostType $custom_post,
     *      EventEspresso\CalendarPlus\CalendarPlusPostMeta $post_meta,
     *      EventEspresso\CalendarPlus\CalendarPlusBlocks $blocks,
     *      EventEspresso\CalendarPlus\CalendarPlus $calendar_plus
     *  ) {
     *      instantiate add-on using above dependencies as needed
     *  }
     *
     * @param CalendarPlusConfig   $config
     * @param CalendarPlusAPI      $api
     * @param CalendarPlusModule   $module
     * @param Assets               $assets
     * @param EventDataHandler     $data_handler
     * @param CalendarPlusPostType $custom_post
     * @param CalendarPlusPostMeta $post_meta
     * @param CalendarPlusBlocks   $blocks
     * @since 1.0.14
     */
    private function loadAddons(
        CalendarPlusConfig $config,
        CalendarPlusAPI $api,
        CalendarPlusModule $module,
        Assets $assets,
        EventDataHandler $data_handler,
        CalendarPlusPostType $custom_post,
        CalendarPlusPostMeta $post_meta,
        CalendarPlusBlocks $blocks
    ): void {
        do_action(
            'EventsCalendarPlusInitialization',
            $this->request,
            $config,
            $api,
            $module,
            $assets,
            $data_handler,
            $custom_post,
            $post_meta,
            $blocks,
            $this
        );
    }


    public function resetMigrations(): void
    {
        $migrations_admin = new MigrationsAdmin($this->request, $this->pluginSlug(), $this->version());
        $migrations_admin->resetMigrations();
    }


    public function printVersion()
    {
        printf('<meta name="%s-version" content="%s">', $this->pluginSlug(), $this->version());
    }
}
