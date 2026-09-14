<?php

function loadEventsCalendarPlus() {
    $calendar_plus = new EventEspresso\CalendarPlus\CalendarPlus(
        new EventEspresso\CalendarPlus\tools\Request(),
        EVENTS_CALENDAR_PLUS_SLUG,
        EVENTS_CALENDAR_PLUS_VERSION
    );
    $calendar_plus->registerHooks();
}

function eventsCalendarPlusMinPhpError() {
    echo '
    <div class="error">
        <p>
            ' .  sprintf(
            esc_html__(
                'Events Calendar Plus requires at least PHP version %s or higher.',
                'events-calendar-plus'
            ),
            EVENTS_CALENDAR_PLUS_MIN_PHP_VERSION
        ) . '
        </p>
    </div>';
}
