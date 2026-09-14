<?php

namespace EventEspresso\CalendarPlus\frontend\models;

use DateInterval;
use DateTimeImmutable;
use EventEspresso\CalendarPlus\api\DateTimeHelper;

/**
 * CalendarEvent - DTO for calendar event data
 *
 * ╔════════════════════════════════════════════════════════════════════════════╗
 * ║ ██ ███    ███ ██████   ██████  ██████  ████████  █████  ███    ██ ████████ ║
 * ║ ██ ████  ████ ██   ██ ██    ██ ██   ██    ██    ██   ██ ████   ██    ██    ║
 * ║ ██ ██ ████ ██ ██████  ██    ██ ██████     ██    ███████ ██ ██  ██    ██    ║
 * ║ ██ ██  ██  ██ ██      ██    ██ ██   ██    ██    ██   ██ ██  ██ ██    ██    ║
 * ║ ██ ██      ██ ██       ██████  ██   ██    ██    ██   ██ ██   ████    ██    ║
 * ╠════════════════════════════════════════════════════════════════════════════╣
 * ║      All DateTime values passed to this class MUST already be in the       ║
 * ║                           site's local timezone.                           ║
 * ║                                                                            ║
 * ║  Timezone conversion (UTC → site TZ) is the responsibility of each         ║
 * ║  adapter (CalendarPlusEvent, EventEspressoEvent, etc.) before              ║
 * ║  constructing a CalendarEvent instance.                                    ║
 * ╚════════════════════════════════════════════════════════════════════════════╝
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus
 * @author      Brent Christensen
 */
class CalendarEvent
{
    public const EVENT_META_KEY = 'CalendarPlusEventData';


    private bool $all_day;

    private string $class_name;

    private string $description;

    private ?DateTimeImmutable $end;

    private string $event_type;

    private int $event_days;

    private int $ID;

    private string $image;

    private DateInterval $interval;

    private DateTimeImmutable $start;

    private string $tags;

    private string $title;

    private string $url;

    private Venue $venue;


    /**
     * @param int               $id
     * @param string            $title
     * @param string            $description
     * @param string            $event_type
     * @param string            $url
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable|null $end
     * @param bool              $all_day
     * @param string            $image
     * @param string            $class_name
     * @param string            $tags
     * @param Venue|null        $venue
     */
    public function __construct(
        int $id,
        string $title,
        string $description,
        string $event_type,
        string $url,
        DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        bool $all_day = false,
        string $image = '',
        string $class_name = '',
        string $tags = '',
        ?Venue $venue = null
    ) {
        $this->ID          = $id;
        $this->all_day     = $all_day;
        $this->class_name  = $class_name;
        $this->description = $description;
        $this->end         = $end;
        $this->interval    = $start->diff($end);
        $this->event_days  = $this->interval->days;
        $this->event_type  = $event_type;
        $this->image       = $image;
        $this->start       = $start;
        $this->tags        = $tags;
        $this->title       = $title;
        $this->url         = $url;
        $this->venue       = $venue;
    }


    private function getStartDate(): string
    {
        return DateTimeHelper::formatDateAndTimeForAPI($this->start);
    }


    private function getEndDate(): string
    {
        return $this->end
            ? DateTimeHelper::formatDateAndTimeForAPI($this->end)
            : '';
    }


    private function generateUID(): string
    {
        $UID = $this->ID
            . $this->title
            . $this->start->format('U')
            . $this->end->format('U')
            . $this->venue->name();
        $UID = md5($UID);
        return substr($UID, 0, 4) . substr($UID, -4, 4);
    }


    public function toArray(): array
    {
        return [
            'UID'         => $this->generateUID(),
            'allDay'      => $this->all_day,
            'className'   => $this->class_name,
            'description' => $this->description,
            'end'         => $this->getEndDate(),
            'eventDays'   => $this->event_days,
            'eventID'     => $this->ID,
            'eventType'   => $this->event_type,
            'image'       => $this->image,
            'start'       => $this->getStartDate(),
            'tags'        => $this->tags,
            'title'       => $this->title,
            'url'         => $this->url,
            'venue'       => [
                'address'   => $this->venue->address(),
                'city'      => $this->venue->city(),
                'country'   => $this->venue->country(),
                'id'        => $this->venue->ID(),
                'isVirtual' => $this->venue->isVirtual(),
                'name'      => $this->venue->name(),
                'state'     => $this->venue->state(),
                'url'       => $this->venue->url(),
            ],
			// kept temporarily for backwards compatibility
			'address'   => $this->venue->address(),
			'city'      => $this->venue->city(),
			'country'   => $this->venue->country(),
			'state'     => $this->venue->state(),
        ];
    }
}
