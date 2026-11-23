<?php

namespace EventEspresso\CalendarPlus\api;

/**
 * CalendarPlusSettings
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\api
 * @author      Mohsin Sabir
 * @since       1.0.0
 */
class CalendarPlusConfig
{
    const SETTINGS_VERSION = 2;

    const OPTION_NAME      = 'events_calendar_plus_settings';

    const UPDATE_FAILED    = -1;

    const UPDATE_NONE      = 0;

    const UPDATE_SUCCESS   = 1;

    private array $settings = [];

    private array $defaults = [];


    public function initialize(): void
    {
        $this->settings = $this->loadSettings();
    }


    public function defaultSettings(): array
    {
        return $this->defaults['new_defaults'] ?? [];
    }


    private function loadDefaultsFile(): void
    {
        $this->defaults = require __DIR__ . '/defaultSettings.php';
    }


    private function loadSettings(): array
    {
        $this->loadDefaultsFile();

        $saved       = (array) get_option(CalendarPlusConfig::OPTION_NAME, []);
        $oldDefaults = $this->defaults['old_defaults'] ?? [];
        $newDefaults = $this->defaults['new_defaults'] ?? [];

        $currentVersion = (int) ($saved['settings_version'] ?? 0);

        if ($currentVersion < CalendarPlusConfig::SETTINGS_VERSION) {
            $migrated                     = $this->smartMerge($saved, $oldDefaults, $newDefaults);
            $migrated['settings_version'] = CalendarPlusConfig::SETTINGS_VERSION;
            update_option(CalendarPlusConfig::OPTION_NAME, $migrated);
            return $migrated;
        }

        return $saved;
    }


    private function smartMerge(array $saved, array $oldDefaults, array $newDefaults): array
    {
        $result = $saved;

        foreach ($newDefaults as $key => $newValue) {
            if (! array_key_exists($key, $saved)) {
                // Not set by user, use new default
                $result[ $key ] = $newValue;
            } elseif ($key === 'styles' && is_array($newValue)) {
                // Handle styles separately for light and dark
                $result['styles'] = $result['styles'] ?? [];

                foreach (['light', 'dark'] as $theme) {
                    $savedTheme = $saved['styles'][ $theme ] ?? [];
                    $oldTheme   = $oldDefaults['styles'][ $theme ] ?? [];
                    $newTheme   = $newDefaults['styles'][ $theme ] ?? [];

                    // Check if user modified any key
                    $userModified = false;
                    foreach ($oldTheme as $styleKey => $oldStyleValue) {
                        if (
                            array_key_exists($styleKey, $savedTheme) &&
                            $savedTheme[ $styleKey ] !== $oldStyleValue
                        ) {
                            $userModified = true;
                            break;
                        }
                    }

                    if (! $userModified) {
                        // No user customization, replace with new theme
                        $result['styles'][ $theme ] = $newTheme;
                    } else {
                        // User modified theme, preserve it
                        $result['styles'][ $theme ] = $savedTheme;
                    }
                }
            }
        }

        return $result;
    }


    /**
     * @param bool $add_nonce
     * @return array
     */
    public function getSettings(bool $add_nonce = false): array
    {
        $settings = [
                'apiUrl' => CalendarPlusAPI::endpointURL(),
                'settingsApiUrl' => CalendarPlusAPI::endpointURL(true),
                'currency' => (new CurrencyData())->resolve(),
            ] + $this->settings;
        if ($add_nonce) {
            $settings['nonce'] = wp_create_nonce(CalendarPlusAPI::NONCE_ACTION_UPDATE_SETTINGS);
        }
        return $settings;
    }


    public function updateSettings(array $settings): int
    {
        // check if settings have actually changed
        if ($this->settings == $settings) {
            return CalendarPlusConfig::UPDATE_NONE;
        }
        return update_option(CalendarPlusConfig::OPTION_NAME, $settings)
            ? CalendarPlusConfig::UPDATE_SUCCESS
            : CalendarPlusConfig::UPDATE_FAILED;
    }
}
