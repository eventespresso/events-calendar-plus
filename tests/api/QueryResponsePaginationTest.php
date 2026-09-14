<?php

declare(strict_types=1);

namespace EventEspresso\CalendarPlus\tests\api;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use EventEspresso\CalendarPlus\api\DateRange;
use EventEspresso\CalendarPlus\api\QueryResponse;
use EventEspresso\CalendarPlus\frontend\adaptors\EventAdaptor;
use EventEspresso\CalendarPlus\frontend\models\CalendarEvent;
use EventEspresso\CalendarPlus\frontend\models\Venue;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Regression coverage for QueryResponse offset pagination.
 *
 * The bug: processResponse() decided whether more pages existed by comparing
 * the emitted CalendarEvent count against $offset/$total, which are counted in
 * a different unit. A 1:many adaptor emits more CalendarEvents than source rows,
 * so the count ran ahead of the offset and the final page was silently dropped.
 *
 * These tests assert on the SEQUENCE OF OFFSETS requested, since that is what
 * actually regressed, and confirm the previously-dropped events are reachable.
 *
 * @group pagination
 */
class QueryResponsePaginationTest extends TestCase
{
    /**
     * A 1:many source (e.g. EventEspressoEvent: one event, many datetimes) whose
     * per-page emitted count (52) exceeds the query limit (50). Under the old
     * count()-based test the third page reached 52 + 100 = 152, not < 152, so
     * offset 150 was never requested and its two events vanished.
     */
    public function testOneToManySourceRequestsFinalPageAndKeepsItsEvents(): void
    {
        $source = $this->oneToManyAdaptor();

        $result = $this->drivePagination($source);

        // The termination test is the regression: offset 150 must be requested.
        $this->assertSame([0, 50, 100, 150], $source->offsetsRequested);
        $this->assertFalse($result['hasMore']);
        $this->assertSame(152, $result['offset']);
        // The two events that used to be dropped are only emitted at offset 150.
        $this->assertContains(40195, $result['eventIDs']);
        $this->assertContains(40473, $result['eventIDs']);
    }

    /**
     * A 1:1 source (e.g. CalendarPlusEvent: one post, one CalendarEvent) where
     * emitted count already matched the offset unit. It was never broken; this
     * locks in that the offset-driven condition stays equivalent for it.
     */
    public function testOneToOneSourcePaginatesToCompletion(): void
    {
        $source = $this->oneToOneAdaptor();

        $result = $this->drivePagination($source);

        $this->assertSame([0, 50, 100], $source->offsetsRequested);
        $this->assertFalse($result['hasMore']);
        $this->assertSame(150, $result['offset']);
        $this->assertCount(150, $result['eventIDs']);
    }

    /**
     * Drive QueryResponse exactly as the REST client does: start at offset 0 and,
     * while the response reports hasMore, re-request at the returned offset.
     *
     * @return array{offset:int, hasMore:bool, total:int, eventIDs:int[]}
     */
    private function drivePagination(EventAdaptor $source): array
    {
        $range    = new DateRange(new DateTime('2026-07-01'), new DateTime('2026-09-30'));
        $offset   = 0;
        $eventIDs = [];
        $guard    = 0;
        do {
            $response = new QueryResponse($source, $offset);
            $response->getEventsForDateRange($range);
            $page = $response->processResponse();
            foreach ($page['events'] as $event) {
                $eventIDs[] = $event['eventID'];
            }
            $offset  = $page['offset'];
            $hasMore = $page['hasMore'];
            if (++$guard > 20) {
                throw new RuntimeException('runaway pagination loop');
            }
        } while ($hasMore);

        return [
            'offset'   => $offset,
            'hasMore'  => $hasMore,
            'total'    => $page['total'],
            'eventIDs' => $eventIDs,
        ];
    }

    private function oneToManyAdaptor(): EventAdaptor
    {
        return new class ($this) extends EventAdaptor {
            public const SLUG = 'one-to-many';

            /** @var int[] */
            public array $offsetsRequested = [];

            private QueryResponsePaginationTest $test;

            public function __construct(QueryResponsePaginationTest $test)
            {
                parent::__construct();
                $this->test = $test;
            }

            public function isApplicable(): bool
            {
                return true;
            }

            public function getEventCategories(int $ID = 0): array
            {
                return [];
            }

            public function getEventTags(int $ID = 0): array
            {
                return [];
            }

            public function totalEventCount(DateRange $date_range): int
            {
                return 152;
            }

            public function getEventsForDateRange(DateRange $date_range, int $offset = 0): array
            {
                $this->offsetsRequested[] = $offset;
                if ($offset >= 150) {
                    // The two events the bug dropped (real IDs from the diagnosis).
                    return [$this->test->makeCalendarEvent(40195), $this->test->makeCalendarEvent(40473)];
                }
                $events = [];
                for ($i = 0; $i < 52; $i++) {
                    $events[] = $this->test->makeCalendarEvent($offset * 1000 + $i);
                }
                return $events;
            }
        };
    }

    private function oneToOneAdaptor(): EventAdaptor
    {
        return new class ($this) extends EventAdaptor {
            public const SLUG = 'one-to-one';

            /** @var int[] */
            public array $offsetsRequested = [];

            private QueryResponsePaginationTest $test;

            public function __construct(QueryResponsePaginationTest $test)
            {
                parent::__construct();
                $this->test = $test;
            }

            public function isApplicable(): bool
            {
                return true;
            }

            public function getEventCategories(int $ID = 0): array
            {
                return [];
            }

            public function getEventTags(int $ID = 0): array
            {
                return [];
            }

            public function totalEventCount(DateRange $date_range): int
            {
                return 150;
            }

            public function getEventsForDateRange(DateRange $date_range, int $offset = 0): array
            {
                $this->offsetsRequested[] = $offset;
                $events = [];
                for ($i = 0; $i < 50; $i++) {
                    $events[] = $this->test->makeCalendarEvent($offset * 1000 + $i);
                }
                return $events;
            }
        };
    }

    public function makeCalendarEvent(int $id): CalendarEvent
    {
        $start = new DateTimeImmutable('2026-09-30 22:00:00', new DateTimeZone('UTC'));
        $end   = $start->add(new DateInterval('PT1H'));
        return new CalendarEvent(
            $id,
            "Event $id",
            '',
            'event',
            "https://example.test/$id",
            $start,
            $end,
            false,
            '',
            '',
            '',
            new Venue($id, "Venue $id")
        );
    }
}
