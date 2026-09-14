<?php

namespace EventEspresso\CalendarPlus\frontend\adaptors;

use EventEspresso\CalendarPlus\api\DateRange;
use EventEspresso\CalendarPlus\api\DateTimeHelper;
use EventEspresso\CalendarPlus\CalendarPlusPostMeta;
use EventEspresso\CalendarPlus\CalendarPlusPostType;
use EventEspresso\CalendarPlus\frontend\models\CalendarEvent;
use EventEspresso\CalendarPlus\frontend\models\Venue;
use Exception;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * CalendarPlusEvent
 * retrieve calendar_plus_event custom post types and convert to CalendarEvent
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\frontend\adaptors
 * @author      Brent Christensen
 * @since       1.0.1
 */
class CalendarPlusEvent extends EventAdaptor
{
    public const SLUG = 'calendarPlus';


    public function isApplicable(): bool
    {
        return true;
    }


    /**
     * @return CalendarEvent[]
     */
    private function convertResultsToCalendarEvents(array $posts): array
    {
        $calendar_events = [];
        foreach ($posts as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }
            $calendar_event = $this->createCalendarEvent($post);
            if (! $calendar_event instanceof CalendarEvent) {
                continue;
            }
            $calendar_events[] = $calendar_event;
        }
        return $calendar_events;
    }


    /**
     * @return string[]
     */
    public function getEventCategories(int $ID = 0): array
    {
        $categories = $ID
            ? get_the_terms($ID, CalendarPlusPostType::CAT_TAX)
            : get_terms(
                [
                    'taxonomy'   => CalendarPlusPostType::CAT_TAX,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'hide_empty' => false,
                ]
            );

        if (is_wp_error($categories)) {
            if (WP_DEBUG) {
                error_log(
                    esc_html(
                        sprintf(
                        /* translators: 1: post id 2: error message */
                            __('Failed to retrieve categories for event ID %1$d: %2$s', 'events-calendar-plus'),
                            $ID,
                            $categories->get_error_message()
                        )
                    )
                );
            }
            return [];
        }

        if (! $categories) {
            return [];
        }

        return array_map(fn(WP_Term $category) => html_entity_decode($category->name), $categories);
    }


    public function getEventTags(int $ID = 0): array
    {
        $tags = $ID
            ? get_the_terms($ID, CalendarPlusPostType::TAG_TAX)
            : get_terms(
                [
                    'taxonomy'   => CalendarPlusPostType::TAG_TAX,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'hide_empty' => false,
                ]
            );

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

        if (! $tags) {
            return [];
        }

        return array_map(fn(WP_Term $tag) => html_entity_decode($tag->name), $tags);
    }


    private function createCalendarEvent(WP_Post $post): ?CalendarEvent
    {
        try {
            $post_ID = $post->ID;
            $start_datetime = CalendarPlusPostMeta::startDateForCalendarEvent($post_ID);
            $end_datetime = CalendarPlusPostMeta::endDateForCalendarEvent($post_ID);

            if (! $start_datetime || ! $end_datetime) {
                return null;
            }

            // Convert UTC datetimes (as stored in DB) to site timezone before passing to CalendarEvent.
            $start_datetime = DateTimeHelper::setTimezoneToSiteTimezone($start_datetime);
            $end_datetime   = DateTimeHelper::setTimezoneToSiteTimezone($end_datetime);

            $categories       = $this->getEventCategories($post_ID);
            $primary_category = ! empty($categories) ? $categories[0] : '';

            $tags        = $this->getEventTags($post_ID);
            $tags_string = implode(', ', $tags);

            return new CalendarEvent(
                $post_ID,
                $post->post_title,
                $post->post_content,
                $primary_category,
                get_permalink($post),
                $start_datetime,
                $end_datetime,
                CalendarPlusPostMeta::isAllDay($post_ID),
                get_the_post_thumbnail_url($post_ID, 'large'),
                CalendarPlusPostMeta::cssClass($post_ID),
                $tags_string,
				new Venue(
					0,
	                CalendarPlusPostMeta::venue($post_ID),
	                CalendarPlusPostMeta::address($post_ID),
	                CalendarPlusPostMeta::city($post_ID),
	                CalendarPlusPostMeta::state($post_ID),
	                CalendarPlusPostMeta::country($post_ID)
				)
            );
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log($e->getMessage());
            }
            return null;
        }
    }


    /**
     * @param DateRange $date_range DateRange object
     * @param int       $offset     [optional] offset for pagination, defaults to $this->query_limit
     * @return CalendarEvent[]
     */
    public function getEventsForDateRange(DateRange $date_range, int $offset = 0): array
    {
        try {
            $query = new WP_Query(
                [
                    'post_type'      => CalendarPlusPostType::EVENT,
                    'post_status'    => 'publish',
                    'posts_per_page' => $this->query_limit,
                    'offset'         => $offset,
                    'meta_query'     => $this->getMetaQueryForDateRange($date_range)
                ]
            );
            return $this->convertResultsToCalendarEvents($query->posts);
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log($e->getMessage());
            }
        }
        return [];
    }


    private function getMetaQueryForDateRange(DateRange $date_range): array
    {
        return [
            [
                'key'     => 'calendar_event_start_datetime',
                'value'   => [$date_range->startString(), $date_range->endString()],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME',
            ],
        ];
    }


    /**
     * set the total number of events
     *
     * @param DateRange $date_range
     * @return int
     * @since 1.0.5
     */
    public function totalEventCount(DateRange $date_range): int
    {
        try {
            $query = new WP_Query(
                [
                    'post_type'      => CalendarPlusPostType::EVENT,
                    'post_status'    => 'publish',
                    'posts_per_page' => 1, // we only need the count
                    'fields'         => 'ids',
                    'meta_query'     => $this->getMetaQueryForDateRange($date_range)
                ]
            );
            $this->event_count = absint($query->found_posts);
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log($e->getMessage());
            }
        }
        return $this->event_count;
    }
}
