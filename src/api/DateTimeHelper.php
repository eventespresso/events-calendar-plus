<?php

namespace EventEspresso\CalendarPlus\api;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * DateTimeHelper
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\api
 * @author      Brent Christensen
 * @since       1.0.1
 */
class DateTimeHelper
{
    private static string $date_format;

    private static string $time_format;

    private static string $site_format;

    private static string $db_format = Database::MYSQL_DATETIME_FORMAT;

    private static string $date_local_input_format = 'Y-m-d\TH:i';

    private static DateTimeZone $WP_TZ;

    private static DateTimeZone $UTC_TZ;


    public static function initialize()
    {
        DateTimeHelper::$WP_TZ       = wp_timezone();
        DateTimeHelper::$UTC_TZ      = new DateTimeZone('UTC');
        DateTimeHelper::$date_format = get_option('date_format');
        DateTimeHelper::$time_format = get_option('time_format');
        DateTimeHelper::$site_format = DateTimeHelper::$date_format . ' ' . DateTimeHelper::$time_format;
    }


    public static function siteFormat(): string
    {
        return self::$site_format;
    }


    public static function siteTimezone(): DateTimeZone
    {
        return DateTimeHelper::$WP_TZ;
    }


    public static function utcTimezone(): DateTimeZone
    {
        return DateTimeHelper::$UTC_TZ;
    }


    public static function convertDatetimeToImmutable(DateTime $datetime): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($datetime);
    }


    public static function convertUnixTimestampToDateTime(int $timestamp): DateTime
    {
        return new DateTime("@$timestamp");
    }


    public static function convertStringToDateTime(
        string $datetime_string,
        string $datetime_format = '',
        ?DateTimeZone $timezone = null
    ): ?DateTimeInterface {
        try {
            if (! $datetime_string) {
                return null;
            }
            if ($datetime_format) {
                $datetime = DateTime::createFromFormat($datetime_format, $datetime_string, $timezone);
                if ($datetime instanceof DateTime) {
                    return $datetime;
                }
                throw new RuntimeException(sprintf('Invalid datetime format: "%1$s"', $datetime_format));
            }
            return new DateTime($datetime_string, $timezone);
        } catch (Exception $exception) {
            throw new RuntimeException(
                esc_html(
                    sprintf(
                    /* translators: 1: datetime string (ex: Nov 1, 2025) 2: error message */
                        __('Error converting "%1$s" to DateTime: %2$s', 'events-calendar-plus'),
                        $datetime_string,
                        $exception->getMessage()
                    )
                )
            );
        }
    }


    /**
     * @param DateTimeInterface|string|null $datetime_or_string
     * @param string                        $datetime_format
     * @return DateTimeInterface|null
     */
    public static function convertSiteTimezoneToUTC(
        $datetime_or_string,
        string $datetime_format = ''
    ): ?DateTimeInterface {
        $datetime = DateTimeHelper::ensureIsDatetime(
            $datetime_or_string,
            $datetime_format,
            DateTimeHelper::siteTimezone()
        );

        if (DateTimeHelper::timeZoneIsUTC($datetime)) {
            return $datetime;
        }

        return DateTimeHelper::setTimezoneToUtc($datetime);
    }


    /**
     * @param DateTimeInterface|string|null $datetime_or_string
     * @param string                        $datetime_format
     * @return DateTimeInterface|null
     */
    public static function convertUtcToSiteTimezone(
        $datetime_or_string,
        string $datetime_format = ''
    ): ?DateTimeInterface {

        if (empty($datetime_or_string)) {
            return null;
        }

        $datetime = DateTimeHelper::ensureIsDatetime(
            $datetime_or_string,
            $datetime_format,
            DateTimeHelper::utcTimezone()
        );

        if (! $datetime instanceof DateTimeInterface) {
            return null;
        }

        if (DateTimeHelper::timeZoneIsSiteTimezone($datetime)) {
            return $datetime;
        }
        return DateTimeHelper::setTimezoneToSiteTimezone($datetime);
    }


    public static function datesAreSameDay(DateTimeInterface $first_date, DateTimeInterface $second_date): bool
    {
        return $first_date->format("Y-m-d") === $second_date->format("Y-m-d");
    }


    private static function ensureIsDatetime(
        $datetime_or_string,
        string $datetime_format = '',
        ?DateTimeZone $timezone = null
    ): ?DateTimeInterface {
        if ($datetime_or_string instanceof DateTimeInterface) {
            return $datetime_or_string;
        }
        if (is_string($datetime_or_string)) {
            return DateTimeHelper::convertStringToDateTime($datetime_or_string, $datetime_format, $timezone);
        }
        throw new RuntimeException(
            esc_html(
                sprintf(
                /* translators: datetime string (ex: Nov 1, 2025) */
                    __('Expected a DateTime object or string, but received: %s', 'events-calendar-plus'),
                    var_export($datetime_or_string, true)
                )
            )
        );
    }


    /**
     * given a DateTime object, return a string formatted for the database using the site's db format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatDateTimeForDatabase(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DateTimeHelper::$db_format)
            : $datetime;
    }


    /**
     * given a DateTime object, return a string formatted for display using the site's date format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatDateForDisplay(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DateTimeHelper::$date_format)
            : $datetime;
    }


    /**
     * given a DateTime object, return a string formatted for display using the DATE_ATOM date and time format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatDateAndTimeForAPI(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DATE_ATOM)
            : $datetime;
    }


    /**
     * given a DateTime object, return a string formatted for display using the site's date and time format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatDateAndTimeForDisplay(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DateTimeHelper::$site_format)
            : $datetime;
    }


    /**
     * given a DateTime object, return a string formatted for display using the site's time format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatTimeForDisplay(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DateTimeHelper::$time_format)
            : $datetime;
    }


    /**
     * given a DateTime object, return a string formatted for display using the site's date and time format
     *
     * @param DateTimeInterface|null $datetime
     * @return string
     */
    public static function formatDateAndTimeForInput(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface
            ? $datetime->format(DateTimeHelper::$date_local_input_format)
            : '';
    }


    /**
     * @param DateTimeInterface $datetime
     * @return DateTimeInterface
     */
    public static function setTimezoneToUtc(DateTimeInterface $datetime): DateTimeInterface
    {
        return $datetime->setTimezone(DateTimeHelper::$UTC_TZ);
    }


    /**
     * @param DateTimeInterface $datetime
     * @return DateTimeInterface
     */
    public static function setTimezoneToSiteTimezone(DateTimeInterface $datetime): DateTimeInterface
    {
        return $datetime->setTimezone(DateTimeHelper::$WP_TZ);
    }


    /**
     * @param DateTimeInterface|string|null $datetime_or_string
     * @return bool
     */
    public static function timeZoneIsUTC($datetime_or_string): bool
    {
        return DateTimeHelper::ensureIsDatetime($datetime_or_string)->getTimezone()->getName() === 'UTC';
    }


    /**
     * @param DateTimeInterface|string|null $datetime_or_string
     * @return bool
     */
    public static function timeZoneIsSiteTimezone($datetime_or_string): bool
    {
        $datetime = DateTimeHelper::ensureIsDatetime($datetime_or_string);
        return $datetime->getTimezone()->getName() === DateTimeHelper::$WP_TZ->getName();
    }


    public static function timezoneOffset(?DateTimeInterface $datetime): string
    {
        return $datetime instanceof DateTimeInterface ? $datetime->format('P') : '00:00';
    }
}
