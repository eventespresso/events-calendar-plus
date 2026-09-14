<?php

namespace EventEspresso\CalendarPlus;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use EventEspresso\CalendarPlus\api\Database;
use EventEspresso\CalendarPlus\api\DateTimeHelper;
use Throwable;
use WP_Error;
use WP_REST_Response;

/**
 * CalendarPlusPostMeta
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus
 * @author      Brent Christensen
 * @since       $VID:
 */
class CalendarPlusPostMeta
{
    //CalendarPlusPostType::EVENT .
    public const KEY_ADDRESS    = 'calendar_event_address';

    public const KEY_ALL_DAY    = 'calendar_event_all_day';

    public const KEY_CITY       = 'calendar_event_city';

    public const KEY_COUNTRY    = 'calendar_event_country';

    public const KEY_CSS_CLASS  = 'calendar_event_css_class';

    public const KEY_END_DATE   = 'calendar_event_end_datetime';

    public const KEY_START_DATE = 'calendar_event_start_datetime';

    public const KEY_STATE      = 'calendar_event_state';

    public const KEY_VENUE      = 'calendar_event_venue';


    public function registerHooks(): void
    {
        add_action('init', [$this, 'registerPostMeta'], 110);
        add_filter('rest_prepare_' . CalendarPlusPostType::EVENT, [$this, 'convertDatetimesForEditor']);
    }


    public function registerPostMeta()
    {
        $meta_properties = $this->metaProperties();
        $prop_context    = ['view', 'edit', 'embed'];
        foreach ($meta_properties as $meta_key => $args) {
            try {
                register_post_meta(
                    CalendarPlusPostType::EVENT,
                    $meta_key,
                    [
                        'type'              => $args['type'],
                        'description'       => $args['description'],
                        'single'            => true,
                        'show_in_rest'      => [
                            'schema' => [
                                'type'    => $args['type'],
                                'context' => $prop_context,
                            ],
                        ],
                        'sanitize_callback' => $args['sanitize_callback'],
                        'auth_callback'     => fn() => current_user_can('edit_posts'),
                    ]
                );
            } catch (Throwable $e) {
                $error_message = sprintf(
                    esc_html__(
                        '[%s] Failed to register post meta "%s" for post type "%s": %s in %s on line %d',
                        'events-calendar-plus'
                    ),
                    __METHOD__,
                    $meta_key,
                    CalendarPlusPostType::EVENT,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                );
                add_action(
                    'admin_notices',
                    function () use ($error_message) {
                        echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
                    }
                );
                error_log($error_message);
            }
        }
    }


    /**
     * Convert UTC datetimes to site timezone for the block editor
     */
    public function convertDatetimesForEditor(WP_REST_Response $response): WP_REST_Response
    {
        if (! isset($response->data['meta'])) {
            return $response;
        }

        $datetime_fields = [
            CalendarPlusPostMeta::KEY_START_DATE,
            CalendarPlusPostMeta::KEY_END_DATE,
        ];

        foreach ($datetime_fields as $field) {
            if (isset($response->data['meta'][ $field ]) && ! empty($response->data['meta'][ $field ])) {
                $datetime = DateTimeHelper::convertUtcToSiteTimezone($response->data['meta'][ $field ], Database::MYSQL_DATETIME_FORMAT);
                if ($datetime instanceof DateTimeInterface) {
                    $response->data['meta'][ $field ] = DateTimeHelper::formatDateAndTimeForInput($datetime);
                }
            }
        }

        return $response;
    }


    private function metaProperties(): array
    {
        return [
            CalendarPlusPostMeta::KEY_START_DATE => [
                'type'              => 'string',
                'description'       => __('Event start date/time', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeDate'],
            ],
            CalendarPlusPostMeta::KEY_END_DATE   => [
                'type'              => 'string',
                'description'       => __('Event end date/time', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeDate'],
            ],
            CalendarPlusPostMeta::KEY_ALL_DAY    => [
                'type'              => 'boolean',
                'description'       => __('Is all day event', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeBoolean'],
            ],
            CalendarPlusPostMeta::KEY_VENUE      => [
                'type'              => 'string',
                'description'       => __('Venue', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
            CalendarPlusPostMeta::KEY_ADDRESS    => [
                'type'              => 'string',
                'description'       => __('Address', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
            CalendarPlusPostMeta::KEY_CITY       => [
                'type'              => 'string',
                'description'       => __('City', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
            CalendarPlusPostMeta::KEY_STATE      => [
                'type'              => 'string',
                'description'       => __('State', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
            CalendarPlusPostMeta::KEY_COUNTRY    => [
                'type'              => 'string',
                'description'       => __('Country', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
            CalendarPlusPostMeta::KEY_CSS_CLASS  => [
                'type'              => 'string',
                'description'       => __('CSS class name', 'events-calendar-plus'),
                'sanitize_callback' => [CalendarPlusPostMeta::class, 'sanitizeText'],
            ],
        ];
    }


    public static function sanitizeBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }


    public static function sanitizeText(string $value): string
    {
        return trim(sanitize_text_field($value));
    }


    public static function sanitizeDate($datetime): string
    {
        if (empty($datetime)) {
            return '';
        }
        // convert the start and end datetimes to UTC any time post meta is saved
        $datetime = DateTimeHelper::convertSiteTimezoneToUTC($datetime);

        return DateTimeHelper::formatDateTimeForDatabase($datetime);
    }


    public static function getPostMeta(int $post_ID, ?string $post_meta_key = null)
    {
        $post_meta_key = $post_meta_key ?: CalendarPlusPostType::POST_META_KEY;
        $post_meta     = get_post_meta($post_ID, $post_meta_key, true);
        return CalendarPlusPostMeta::unserializePostMeta($post_meta);
    }


    private static function stringValue(int $post_ID, string $post_meta_key): string
    {
        return CalendarPlusPostMeta::sanitizeText(
            (string) CalendarPlusPostMeta::getPostMeta($post_ID, $post_meta_key)
        );
    }


    private static function booleanValue(int $post_ID, string $post_meta_key): bool
    {
        return CalendarPlusPostMeta::sanitizeBoolean(
            CalendarPlusPostMeta::getPostMeta($post_ID, $post_meta_key)
        );
    }


    private static function datetimeValue(int $post_ID, string $post_meta_key): ?DateTime
    {
        $datetime_string = CalendarPlusPostMeta::stringValue($post_ID, $post_meta_key);
        $datetime = DateTimeHelper::convertUtcToSiteTimezone($datetime_string, Database::MYSQL_DATETIME_FORMAT);
        return $datetime instanceof DateTime ? $datetime : null;
    }


    private static function datetimeForCalendarEvent(int $post_ID, string $post_meta_key): ?DateTimeImmutable
    {
        // Retrieve the raw UTC datetime string directly from the database.
        // Important: Do NOT convert to the site timezone here.
        //
        // Timezone conversion (UTC → site timezone) is handled by the event
        // adapters, which are responsible for returning localized datetime values.
        // Keeping the conversion there avoids duplicate timezone adjustments.
        $datetime_string = CalendarPlusPostMeta::stringValue($post_ID, $post_meta_key);

        $datetime = DateTimeHelper::convertStringToDateTime(
            $datetime_string,
            Database::MYSQL_DATETIME_FORMAT,
            DateTimeHelper::utcTimezone()
        );

        return $datetime instanceof DateTime
            ? DateTimeHelper::convertDatetimeToImmutable($datetime)
            : null;
    }


    private static function datetimeForPostContent(int $post_ID, string $post_meta_key): array
    {
        $datetime = CalendarPlusPostMeta::datetimeValue($post_ID, $post_meta_key);

        return $datetime instanceof DateTime
            ? [
                DateTimeHelper::formatDateForDisplay($datetime),
                DateTimeHelper::formatTimeForDisplay($datetime),
            ]
            : [
                '',
                '',
            ];
    }


    public static function unserializePostMeta($post_meta)
    {
        if (! $post_meta) {
            return null;
        }
        $post_meta = maybe_unserialize($post_meta);
        if ($post_meta instanceof WP_Error) {
            $error_message = sprintf(
                esc_html__(
                    '[%1$s] Failed to unserialize post meta: %2$s',
                    'events-calendar-plus'
                ),
                __METHOD__,
                var_export($post_meta->get_error_message(), true)
            );
            error_log($error_message);
            return null;
        }
        return $post_meta ?: null;
    }


    public static function address(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_ADDRESS);
    }


    public static function isAllDay(int $post_ID): bool
    {
        return CalendarPlusPostMeta::booleanValue($post_ID, CalendarPlusPostMeta::KEY_ALL_DAY);
    }


    public static function city(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_CITY);
    }


    public static function country(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_COUNTRY);
    }


    public static function cssClass(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_CSS_CLASS);
    }


    public static function endDateForCalendarEvent(int $post_ID): ?DateTimeImmutable
    {
        return CalendarPlusPostMeta::datetimeForCalendarEvent($post_ID, CalendarPlusPostMeta::KEY_END_DATE);
    }


    public static function endDateForPostContent(int $post_ID): array
    {
        return CalendarPlusPostMeta::datetimeForPostContent($post_ID, CalendarPlusPostMeta::KEY_END_DATE);
    }


    public static function isSameDay(int $post_ID): bool
    {
        $start_datetime = CalendarPlusPostMeta::datetimeValue($post_ID, CalendarPlusPostMeta::KEY_START_DATE);
        $end_datetime   = CalendarPlusPostMeta::datetimeValue($post_ID, CalendarPlusPostMeta::KEY_END_DATE);

        return $start_datetime instanceof DateTime
            && $end_datetime instanceof DateTime
            && DateTimeHelper::datesAreSameDay($start_datetime, $end_datetime);
    }


    public static function startDateForCalendarEvent(int $post_ID): ?DateTimeImmutable
    {
        return CalendarPlusPostMeta::datetimeForCalendarEvent($post_ID, CalendarPlusPostMeta::KEY_START_DATE);
    }


    public static function startDateForPostContent(int $post_ID): array
    {
        return CalendarPlusPostMeta::datetimeForPostContent($post_ID, CalendarPlusPostMeta::KEY_START_DATE);
    }


    public static function state(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_STATE);
    }


    public static function venue(int $post_ID): string
    {
        return CalendarPlusPostMeta::stringValue($post_ID, CalendarPlusPostMeta::KEY_VENUE);
    }
}
