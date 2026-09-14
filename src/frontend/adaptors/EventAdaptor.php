<?php

namespace EventEspresso\CalendarPlus\frontend\adaptors;

use DateTimeZone;
use EventEspresso\CalendarPlus\api\DateRange;
use EventEspresso\CalendarPlus\frontend\models\CalendarEvent;

/**
 * EventAdaptor
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\frontend\adaptors
 * @author      Brent Christensen
 */
abstract class EventAdaptor
{
    public const DEFAULT_QUERY_LIMIT = 50;

    protected DateTimeZone $time_zone;

    protected int $event_count = 0;

    protected int $query_limit = 0;


    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function __construct()
    {
        // TODO: implement a way to set the query limit from the settings
        $this->query_limit = self::DEFAULT_QUERY_LIMIT;
        $timezone_string = get_option('timezone_string', 'UTC');
        $timezone_string = $timezone_string ?: 'UTC';
        $this->time_zone = new DateTimeZone($timezone_string);
    }


    /**
     * returns true if the adaptor has all dependencies met and is capable of returning data without failing
     * this might entail checking that a required class or file exists
     *
     * @return bool
     */
    abstract public function isApplicable(): bool;


    /**
     * Should return an array of category NAMES
     *
     * @param int $ID [optional] if provided, then return categories for this event
     * @return string[]
     */
    abstract public function getEventCategories(int $ID = 0): array;


    /**
     * Returns all events that start within the specified date range.
     *
     * @param DateRange $date_range DateRange object
     * @param int $offset           [optional] offset for pagination, defaults to $this->query_limit
     * @return CalendarEvent[]
     */
    abstract public function getEventsForDateRange(DateRange $date_range, int $offset = 0): array;


    /**
     * Should return an array of tag NAMES
     *
     * @param int $ID [optional] if provided, then return tags for this event
     * @return string[]
     */
    abstract public function getEventTags(int $ID = 0): array;


    /**
     * set and get the total number of events
     *
     * @param DateRange $date_range
     * @return int
     * @since 1.0.5
     */
    abstract public function totalEventCount(DateRange $date_range): int;


    public function queryLimit(): int
    {
        return $this->query_limit;
    }


    /**
     * returns a name for the adapter in camelCase format
     *
     * @return string
     * @since 1.0.5
     */
    public function slug(): string
    {
        return static::SLUG;
    }
}
