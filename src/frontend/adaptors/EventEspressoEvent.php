<?php

namespace EventEspresso\CalendarPlus\frontend\adaptors;

use EE_Datetime;
use EE_Error;
use EE_Event;
use EE_Capabilities;
use EE_Term;
use EE_Venue;
use EEM_Event;
use EEM_Term;
use EventEspresso\CalendarPlus\api\DateRange;
use EventEspresso\CalendarPlus\api\DateTimeHelper;
use EventEspresso\CalendarPlus\frontend\models\CalendarEvent;
use EventEspresso\CalendarPlus\frontend\models\Venue;
use Exception;
use ReflectionException;
use EE_Country;
use EE_State;

/**
 * EventEspressoEvent
 * retrieve Event Espresso EE_Event custom post types and convert to CalendarEvent
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\frontend\adaptors
 * @author      Brent Christensen
 * @since       1.0.0
 */
class EventEspressoEvent extends EventAdaptor
{
    public const SLUG = 'eventEspresso';

    private array $events_cache = [];

    private array $category_cache = [];

    private array $extra_meta_cache = [];


    public function isApplicable(): bool
    {
        return class_exists('EEM_Event');
    }


    /**
     * @param DateRange $date_range
     * @param array     $events
     * @return CalendarEvent[]
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function convertResultsToCalendarEvents(DateRange $date_range, array $events): array
    {
        $calendar_events = [];
        foreach ($events as $event) {
            if (! $event instanceof EE_Event) {
                continue;
            }
            $this->events_cache[ $event->ID() ] = $event;
            $datetimes                          = $event->datetimes_ordered();
            foreach ($datetimes as $datetime) {
                if (! $datetime instanceof EE_Datetime) {
                    continue;
                }
                if (
                    $datetime->start() < $date_range->startTimestamp()
                    || $datetime->start() > $date_range->endTimestamp()
                ) {
                    continue;
                }
                $calendar_event = $this->createCalendarEvent($event, $datetime);
                if (! $calendar_event instanceof CalendarEvent) {
                    continue;
                }
                $calendar_events[] = $calendar_event;
            }
        }
        return $calendar_events;
    }


    public function getEventCategories(int $ID = 0): array
    {
        if (! $this->isApplicable()) {
            return [];
        }
        $unique_event_types = [];
        try {
            $categories = [];
            if ($ID) {
                if (! isset($this->events_cache[ $ID ])) {
                    // If the event is not in the cache, fetch it from the database
                    $event = EEM_Event::instance()->get_one_by_ID($ID);
                    if ($event instanceof EE_Event) {
                        $this->events_cache[ $ID ] = $event;
                    }
                }
                $event = $this->events_cache[ $ID ] ?? null;
                if ($event instanceof EE_Event) {
                    if (! isset($this->category_cache[ $ID ])) {
                        $this->category_cache[ $ID ] = $event->get_all_event_categories();
                    }
                    $categories = $this->category_cache[ $ID ] ?? [];
                }
            } else {
                $categories = EEM_Term::instance()->get_all(
                    [['Term_Taxonomy.taxonomy' => 'espresso_event_categories']]
                );
            }
            foreach ($categories as $category) {
                if ($category instanceof EE_Term) {
                    $category_name = html_entity_decode($category->name());
                    if (! in_array($category_name, $unique_event_types, true)) {
                        $unique_event_types[] = $category_name;
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logError($exception);
        }

        return $unique_event_types;
    }


    /**
     * Should return an array of tag NAMES
     *
     * @param int $ID [optional] if provided, then return tags for this event
     * @return string[]
     */
    public function getEventTags(int $ID = 0): array
    {
        $tags = get_the_tags($ID);
        if (! $tags) {
            return [];
        }
        if (is_wp_error($tags)) {
            if (WP_DEBUG) {
                error_log(
                    esc_html(
                        sprintf(
                            /* translators: 1: post id 2: error message */
                            __('Failed to retrieve tags for event ID %1$d: %2$s', 'events-calendar-plus'),
                            $ID,
                            $tags->get_error_message()
                        )
                    )
                );
            }
            return [];
        }
        return array_map(fn($tag) => html_entity_decode($tag->name), $tags);
    }


    /**
     * @param EE_Event $event
     * @return array|null
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function getEventExtraMeta(EE_Event $event): ?array
    {
        // If no cached meta for this event exists
        if (! array_key_exists($event->ID(), $this->extra_meta_cache)) {
            // Save the event meta to cache
            $this->extra_meta_cache[ $event->ID() ] = $event->get_extra_meta(CalendarEvent::EVENT_META_KEY);
        }

        //  and then return it
        return $this->extra_meta_cache[ $event->ID() ];
    }


    private function createCalendarEvent(EE_Event $event, EE_Datetime $datetime): ?CalendarEvent
    {
        try {
            $event_name       = $event->name();
            $date_name        = $datetime->name();
            $event_name       .= $date_name ? " - $date_name" : '';
            $start_date       = DateTimeHelper::convertUnixTimestampToDateTime($datetime->start());
            $end_date         = DateTimeHelper::convertUnixTimestampToDateTime($datetime->end());

            // Convert UTC datetimes (as stored in DB) to site timezone before passing to CalendarEvent.
            $start_date = DateTimeHelper::setTimezoneToSiteTimezone($start_date);
            $end_date   = DateTimeHelper::setTimezoneToSiteTimezone($end_date);

            $venue            = $this->getVenue($event, $datetime);
            $event_meta       = $this->getEventExtraMeta($event);
            $is_all_day       = $event_meta['all_day'] ?? false;
            $event_class_name = $event_meta['class_name'] ?? '';
            $description      = $datetime->description() ?: $event->description();

            $permalink   = $event->get_permalink();
            $datetime_id = $datetime->id();

            $permalink   = $datetime_id
                ? add_query_arg('datetime', $datetime_id, $permalink)
                : $permalink;

            $venue_id   = -1;
            $venue_name = '';
            $address    = '';
            $city       = '';
            $state      = '';
            $country    = '';

            if ($venue instanceof EE_Venue) {
				$venue_id = $venue->ID();
				$venue_name = $venue->name();
                $address = $venue->address();
                $address .= $venue->address2() ? ' ' . $venue->address2() : '';
				$city = $venue->city();
                $country = $venue->country_obj();
                $country = $country instanceof EE_Country ? $country->name() : '';
                $state   = $venue->state_obj();
                $state   = $state instanceof EE_State ? $state->name() : '';
			}

            $categories = $this->getEventCategories($event->ID());
            $categories = implode(', ', $categories);

            $tags = $this->getEventTags($event->ID());
            $tags = implode(', ', $tags);

            return new CalendarEvent(
                $event->ID(),                                               // int $id
                $event_name,                                                // string $title
                $description,                                               // string $description
                $categories,                                                // string $event_type
                esc_url($permalink),                                        // string $url
                DateTimeHelper::convertDatetimeToImmutable($start_date),    // DateTimeImmutable $start
                DateTimeHelper::convertDatetimeToImmutable($end_date),      // DateTimeImmutable $end
                $is_all_day,                                                // bool $all_day = false
                get_the_post_thumbnail_url($event->ID(), 'large'),          // string $image = ''
                $event_class_name,                                          // string $class_name = ''
                $tags,                                                      // string $tags = ''
				new Venue(
					$venue_id,
					$venue_name,
					$address,
					$city,
					$state,
					$country
				)
            );
        } catch (Exception $exception) {
            $this->logError($exception);
        }
        return null;
    }


    /**
     * @param DateRange $date_range DateRange object
     * @param int       $offset     [optional] offset for pagination, defaults to $this->query_limit
     * @return CalendarEvent[]
     * @throws Exception
     */
    public function getEventsForDateRange(DateRange $date_range, int $offset = 0): array
    {
        try {
            $limit = $offset > 0 ? [$offset, $this->query_limit] : $this->query_limit;
            /*
                SELECT *
                FROM wp_posts AS Event_CPT
                    LEFT JOIN wp_esp_event_meta AS Event_Meta ON Event_CPT.ID = Event_Meta.EVT_ID
                    LEFT JOIN wp_esp_datetime AS Datetime ON Datetime.EVT_ID = Event_CPT.ID
                WHERE Event_CPT.post_type = 'espresso_events'
                    AND Event_CPT.post_status IN ('publish', 'sold_out')
                    AND ((Datetime.DTT_deleted = 0) OR Datetime.DTT_ID IS NULL)
                    AND Datetime.DTT_EVT_start >= '2025-05-01 00:00:00'
                    AND Datetime.DTT_EVT_start <= '2025-07-31 23:59:59'
                GROUP BY Event_CPT.ID
                LIMIT 50
             */
            return $this->convertResultsToCalendarEvents(
                $date_range,
                EEM_Event::instance()->get_all(
                    [
                        $this->setWhereConditionsForStatus(
                            [
                                'Datetime.DTT_EVT_start*after'  => [
                                    '>=',
                                    $date_range->startTimestamp(),
                                ],
                                'Datetime.DTT_EVT_start*before' => [
                                    '<=',
                                    $date_range->endTimestamp(),
                                ],
                            ]
                        ),
                        'limit'    => $limit,
                        'group_by' => '', // prevents non-aggregate grouping error
                    ]
                )
            );
        } catch (Exception $exception) {
            $this->logError($exception);
        }
        return [];
    }


    /**
     * set and get the total number of events that start within date range
     *
     * @param DateRange $date_range
     * @return int
     * @since 1.0.5
     */
    public function totalEventCount(DateRange $date_range): int
    {
        try {
            /*
                SELECT COUNT(Event_CPT.ID)
                FROM  wp_posts AS Event_CPT
                    LEFT JOIN wp_esp_event_meta AS Event_Meta ON Event_CPT.ID = Event_Meta.EVT_ID
                    LEFT JOIN wp_esp_datetime AS Datetime ON Datetime.EVT_ID=Event_CPT.ID
                WHERE Event_CPT.post_type = 'espresso_events'
                    AND Event_CPT.post_status IN ('publish','sold_out')
                    AND ((Datetime.DTT_deleted = 0) OR Datetime.DTT_ID IS NULL)
                    AND Datetime.DTT_EVT_start >= '2025-05-01 00:00:00'
                    AND Datetime.DTT_EVT_start <= '2025-07-31 23:59:59'
             */
            $this->event_count = EEM_Event::instance()->count(
                [
                    $this->setWhereConditionsForStatus(
                        [
                            'Datetime.DTT_EVT_start*after'  => [
                                '>=',
                                $date_range->startTimestamp(),
                            ],
                            'Datetime.DTT_EVT_start*before' => [
                                '<=',
                                $date_range->endTimestamp(),
                            ],
                        ]
                    ),
                ]
            );
            return $this->event_count;
        } catch (Exception $exception) {
            $this->logError($exception);
        }
        return 0;
    }


    /**
     * Shim to use built in set_where_conditions_for_status() method if avaiable
     *
     * @param array $where_params
     * @return array
     * @since {VID}
     */
    private function setWhereConditionsForStatus(array $where_params) {
        // Check if this is available within core
        if(method_exists('EEM_Event', 'set_where_conditions_for_status')) {
            return EEM_Event::instance()->set_where_conditions_for_status($where_params);
        }

        // If not use published and sold_out events
        $event_status = ['publish', EEM_Event::sold_out];
        // check if the user can read private events and if so add the 'private' status to the where params
        if (EE_Capabilities::instance()->current_user_can('ee_read_private_events', 'get_upcoming_events')) {
            $event_status[] = 'private';
        }
        $where_params['status'] = ['IN', $event_status];
        return $where_params;
    }


    /**
     * Shim to use built in Datetime/Event venues and fallback to legacy if unavailable
     *
     * @param EE_Event $event
     * @param EE_Datetime $datetime
     * @return null|EE_Venue
     * @since {VID}
     */
    private function getVenue(EE_Event $event, EE_Datetime $datetime) {
        // Venue set on the datetime?
        if(method_exists($datetime, 'venue')) {
            $venue = $datetime->venue();
            if($venue instanceof EE_Venue) {
                return $venue;
            }
        }
        // Venue set on the event? Try to use the venue() method
        if(method_exists($event, 'venue')) {
            $venue = $event->venue();
            if($venue instanceof EE_Venue) {
                return $venue;
            }
        }
        // Still no venue? Fallback to legacy method
        $venue = $event->venues(['limit' => 1]);
        $venue = is_array($venue) ? array_shift($venue) : $venue;
        return $venue instanceof EE_Venue ? $venue : null;
    }


    private function logError(Exception $exception): void
    {
        if (WP_DEBUG) {
            error_log($exception->getMessage());
        }
    }
}
