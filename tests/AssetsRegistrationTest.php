<?php

declare(strict_types=1);

namespace EventEspresso\CalendarPlus\tests;

use EventEspresso\CalendarPlus\Assets;
use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for Assets registering against an already-created registry.
 *
 * The bug: Assets registered its bundles by listening for wp_default_scripts /
 * wp_default_styles, which fire exactly once when WP_Scripts / WP_Styles is first
 * created. If another plugin (e.g. ACF Pro) touched the script registry before
 * Assets::registerHooks() ran, the actions had already fired and the calendarPlus
 * handles were never registered, leaving the frontend calendar and admin app empty.
 *
 * registerHooks() now registers immediately when did_action() reports the registry
 * already exists, and hooks as before otherwise.
 *
 * @group assets
 */
class AssetsRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the faked registry and hooks so each scenario starts un-fired.
        unset($GLOBALS['wp_scripts'], $GLOBALS['wp_styles']);
        $GLOBALS['wp_test_actions']     = [];
        $GLOBALS['wp_test_did_actions'] = [];
    }

    /**
     * Registry already created before registerHooks() — the regression case.
     * The actions have already fired, so registerHooks() must register directly.
     */
    public function testRegistersWhenRegistryCreatedBeforeHooks(): void
    {
        // Creating the registries fires wp_default_scripts / wp_default_styles up front.
        wp_scripts();
        wp_styles();
        $this->assertSame(1, did_action('wp_default_scripts'));
        $this->assertSame(1, did_action('wp_default_styles'));

        (new Assets('events-calendar-plus', '1.0.15'))->registerHooks();

        $this->assertTrue(wp_script_is(Assets::HANDLE_PUBLIC, 'registered'));
        $this->assertTrue(wp_style_is(Assets::HANDLE_PUBLIC, 'registered'));
        $this->assertTrue(wp_script_is(Assets::HANDLE_ADMIN, 'registered'));
        $this->assertTrue(wp_style_is(Assets::HANDLE_ADMIN, 'registered'));
    }

    /**
     * Normal path — registerHooks() runs first, the registry is created afterwards.
     * The hooks it attached must still register the handles when the actions fire.
     */
    public function testRegistersWhenHooksRunBeforeRegistryCreated(): void
    {
        $this->assertSame(0, did_action('wp_default_scripts'));

        (new Assets('events-calendar-plus', '1.0.15'))->registerHooks();

        // Nothing registered until the registry exists and fires the actions.
        wp_scripts();
        wp_styles();

        $this->assertTrue(wp_script_is(Assets::HANDLE_PUBLIC, 'registered'));
        $this->assertTrue(wp_style_is(Assets::HANDLE_PUBLIC, 'registered'));
        $this->assertTrue(wp_script_is(Assets::HANDLE_ADMIN, 'registered'));
        $this->assertTrue(wp_style_is(Assets::HANDLE_ADMIN, 'registered'));
    }
}
