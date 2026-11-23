<?php

namespace EventEspresso\CalendarPlus;

/**
 * CustomView
 *
 * Shared by every custom view add-on (List, Grid, etc.): enqueues its bundle alongside
 * the calendar and on the admin settings page, and registers its handle with Anywhere's
 * embed bundler. Each add-on just supplies its own CustomViewAssets.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus
 * @author      Mohsin Sabir
 * @since       {VID}
 */
class CustomView extends CalendarPlusModule
{
    public const SETTINGS_PAGE_HOOK = 'calendar-event_page_events-calendar-plus-settings';

    public const EMBED_ADDON_HANDLES_FILTER = 'calendar_plus_embed_addon_handles';

    private CustomViewAssets $assets;


    public function __construct(CustomViewAssets $assets, string $plugin_slug, string $version)
    {
        parent::__construct($plugin_slug, $version);
        $this->assets = $assets;
    }


    public function registerHooks(): void
    {
        // Run after C+ enqueues its bundle at priority 99 so wp_script_is() sees it
        add_action('wp_enqueue_scripts', [$this, 'enqueueWithCalendar'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueueOnSettingsPage'], 100);
        // Expose this view's script handle to Calendar Plus Anywhere's embed bundler.
        add_filter(self::EMBED_ADDON_HANDLES_FILTER, [$this, 'registerEmbedAddonHandle']);
    }


    public function enqueueWithCalendar(): void
    {
        if (wp_script_is(Assets::HANDLE_PUBLIC, 'enqueued')) {
            $this->assets->enqueue();
        }
    }


    /**
     * @param string $page Admin page hook suffix, as passed to admin_enqueue_scripts.
     */
    public function enqueueOnSettingsPage(string $page): void
    {
        if ($page === self::SETTINGS_PAGE_HOOK) {
            $this->assets->enqueue();
        }
    }


    /**
     * @param string[] $handles Script handles registered by other custom views so far.
     * @return string[]
     */
    public function registerEmbedAddonHandle(array $handles): array
    {
        $handles[] = $this->assets->handle();
        return $handles;
    }
}
