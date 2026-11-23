<?php

namespace EventEspresso\CalendarPlus;

/**
 * CustomViewAssets
 *
 * Lets CustomView enqueue a custom view add-on's assets and register its handle
 * without depending on the add-on's concrete class.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus
 * @author      Mohsin Sabir
 * @since       {VID}
 */
interface CustomViewAssets
{
    public function handle(): string;

    public function enqueue(): void;
}
