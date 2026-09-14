<?php

namespace EventEspresso\CalendarPlus\migrations\jobs;

use DateTimeInterface;
use EventEspresso\CalendarPlus\CalendarPlusPostMeta;
use EventEspresso\CalendarPlus\api\Database;
use EventEspresso\CalendarPlus\api\DateTimeHelper;
use EventEspresso\CalendarPlus\migrations\MigrationJobProgress;
use EventEspresso\CalendarPlus\migrations\MigrationJob;

/**
 * MigrateEventDateTimesToUtc
 *
 * Converts existing C+ event start/end datetimes from site timezone to UTC.
 *
 * Prior to this migration, event datetimes were stored in the site's local timezone.
 * The new logic requires all datetimes to be stored as UTC so they can be correctly
 * converted to any timezone on retrieval.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\migrations
 */
class MigrateEventDateTimesToUtc extends MigrationJob
{
    private const DB_VERSION = '2.0';

    private ?array $post_IDs = null;


    public function databaseVersion(): string
    {
        return self::DB_VERSION;
    }


    public function jobName(): string
    {
        return 'Migrate Event DateTimes to UTC';
    }


    public function recordType(): string
    {
        return 'WP Post Meta';
    }


    /**
     * Sets up any dependencies and/or prepares the migration.
     *
     * @return void
     */
    public function initializeMigrationJob(): void
    {
        if ($this->post_IDs === null) {
            global $wpdb;
            $post_IDs       = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key IN (%s, %s)",
                    CalendarPlusPostMeta::KEY_START_DATE,
                    CalendarPlusPostMeta::KEY_END_DATE
                )
            );
            $this->post_IDs = is_array($post_IDs) ? $post_IDs : [];
        }
    }


    public function countRecordsToMigrate(): int
    {
        if ($this->post_IDs === null) {
            $this->initializeMigrationJob();
        }
        return count($this->post_IDs);
    }


    /**
     * Returns an array of all record IDs that need to be migrated.
     *
     * @return array
     */
    public function getAllRecordIDs(): array
    {
        if ($this->post_IDs === null) {
            $this->initializeMigrationJob();
        }
        return $this->post_IDs;
    }


    /**
     * Returns the raw start/end datetime meta values for the given post.
     *
     * @param int|string $ID the post ID
     * @return array|null
     */
    public function getNextRecord($ID): ?array
    {
        $start = get_post_meta($ID, CalendarPlusPostMeta::KEY_START_DATE, true);
        $end   = get_post_meta($ID, CalendarPlusPostMeta::KEY_END_DATE, true);

        if (empty($start) && empty($end)) {
            return null;
        }

        return [
            'start' => (string) $start,
            'end'   => (string) $end,
        ];
    }


    /**
     * Converts the event's start/end datetimes from site timezone to UTC and
     * saves them directly via $wpdb to bypass the sanitize_callback (which
     * would otherwise double-convert an already-UTC value).
     *
     * @param int|string $ID   the post ID
     * @param array      $data ['start' => string, 'end' => string]
     * @return int
     */
    public function migrateRecord($ID, array $data): int
    {
        global $wpdb;

        $meta_keys = [
            CalendarPlusPostMeta::KEY_START_DATE => $data['start'] ?? '',
            CalendarPlusPostMeta::KEY_END_DATE   => $data['end'] ?? '',
        ];

        foreach ($meta_keys as $meta_key => $datetime_string) {
            if (empty($datetime_string)) {
                continue;
            }

            // Interpret $datetime_string as site-timezone datetime and convert to UTC.
            $utc_datetime = DateTimeHelper::convertSiteTimezoneToUTC(
                $datetime_string,
                Database::MYSQL_DATETIME_FORMAT
            );

            if (! $utc_datetime instanceof DateTimeInterface) {
                continue;
            }

            $utc_string = DateTimeHelper::formatDateTimeForDatabase($utc_datetime);

            $wpdb->update(
                $wpdb->postmeta,
                ['meta_value' => $utc_string],
                ['post_id' => (int) $ID, 'meta_key' => $meta_key],
                ['%s'],
                ['%d', '%s']
            );

            wp_cache_delete((int) $ID, 'post_meta');
        }

        return MigrationJobProgress::RECORD_MIGRATED;
    }
}
