<?php

namespace EventEspresso\CalendarPlus\migrations;

/**
 * DatabaseSchema
 * Tracks the current database schema version for the Events Calendar Plus plugin.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\migrations
 * @author      Brent Christensen
 * @since       1.0.5
 */
class DatabaseSchema
{
    /**
     * the "target" post migration db schema version that the database should be at
     */
    public const POST_MIGRATION_DB_VERSION = 3;

    /**
     * name of the WP option used to store db version
     */
    private const OPTION_NAME = 'events_calendar_plus_db_version';

    /**
     * the actual current db schema version
     */
    private static ?int $current_db_version = null;


    private static function loadVersion(): void
    {
        if (DatabaseSchema::$current_db_version === null) {
            DatabaseSchema::$current_db_version = DatabaseSchema::initializeDatabaseSchema(false);
        }
    }


    /**
     * @param bool $new_site
     * @return int
     * @since 1.0.5
     */
    public static function initializeDatabaseSchema(bool $new_site = true): int
    {
        $db_version = absint(get_option(DatabaseSchema::OPTION_NAME, 0));
        if (! $db_version) {
            // if this is the first time this plugin has been activated,
            // then just set the db version to the post migration version so that migrations are not required
            $db_version = $new_site ? DatabaseSchema::POST_MIGRATION_DB_VERSION : 1;
            add_option(DatabaseSchema::OPTION_NAME, $db_version, '', 'no');
        }
        return $db_version;
    }


    public static function migrationsAreRequired(): bool
    {
        DatabaseSchema::loadVersion();
        // if the current db version is less than the target db version, then migrations are required
        return DatabaseSchema::$current_db_version < DatabaseSchema::POST_MIGRATION_DB_VERSION;
    }


    /**
     * if the actual current database is at version 1,
     * but the target database version is at 4
     * because the user hadn't updated the plugin for a while,
     * then we need to increment the version number slowly
     * so that we can run the migrations for each version
     *
     * @return int
     */
    public static function incrementVersion(): int
    {
        DatabaseSchema::loadVersion();
        if (DatabaseSchema::$current_db_version < DatabaseSchema::POST_MIGRATION_DB_VERSION) {
            DatabaseSchema::$current_db_version++;
            DatabaseSchema::updateVersion();
        }
        return DatabaseSchema::$current_db_version;
    }


    public static function currentVersion(): int
    {
        DatabaseSchema::loadVersion();
        return DatabaseSchema::$current_db_version;
    }


    public static function postMigrationVersion(): int
    {
        return DatabaseSchema::POST_MIGRATION_DB_VERSION;
    }


    private static function updateVersion(): void
    {
        if (DatabaseSchema::$current_db_version) {
            update_option(DatabaseSchema::OPTION_NAME, DatabaseSchema::$current_db_version);
        }
    }


    public static function reset(): void
    {
        DatabaseSchema::$current_db_version = 1;
        delete_option(DatabaseSchema::OPTION_NAME);
    }
}
