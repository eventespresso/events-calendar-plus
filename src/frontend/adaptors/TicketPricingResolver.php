<?php

namespace EventEspresso\CalendarPlus\frontend\adaptors;

use EE_Config;
use EE_Currency_Config;
use EE_Datetime;
use EE_Error;
use EE_Ticket;
use Exception;
use ReflectionException;

/**
 * TicketPricingResolver
 *
 * Gets the min/max price for a datetime's public, non-trashed tickets.
 * The calendar displays these prices and its price filters compare against them.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\frontend\adaptors
 * @author      Mohsin Sabir
 * @since       {VID}
 */
class TicketPricingResolver
{
    /**
     * @param EE_Datetime $datetime
     * @return array|null ['minPrice' => float, 'maxPrice' => float], or null when the
     *                     datetime has no priced (non-trashed, public) tickets.
     */
    public function forDatetime(EE_Datetime $datetime): ?array
    {
        try {
            // Exclude trashed tickets so they don't affect the min/max price range.
            $where = ['TKT_deleted' => false];
            // Exclude expired tickets when "Show Expired Tickets?" is No.
            if (! $this->showExpiredTickets()) {
                $where['TKT_end_date'] = ['>', time()];
            }
            $tickets = $datetime->tickets([$where]);
            if (empty($tickets)) {
                return null;
            }
            $prices = [];
            foreach ($tickets as $ticket) {
                if ($ticket instanceof EE_Ticket && $this->isPublic($ticket)) {
                    $prices[] = $this->displayedPrice($ticket);
                }
            }
            if (empty($prices)) {
                return null;
            }
            return [
                'minPrice' => min($prices),
                'maxPrice' => max($prices),
            ];
        } catch (Exception $exception) {
            $this->logError($exception);
            return null;
        }
    }


    /**
     * Whether to include expired tickets, read from the "Show Expired Tickets?" setting.
     * Defaults to Yes, as in EE_Ticket_Selector_Config.
     *
     * @return bool
     */
    private function showExpiredTickets(): bool
    {
        return EE_Config::instance()->template_settings->EED_Ticket_Selector->show_expired_tickets ?? true;
    }


    /**
     * Treats every ticket as public if the installed EE core has no ticket visibility.
     *
     * @param EE_Ticket $ticket
     * @return bool
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function isPublic(EE_Ticket $ticket): bool
    {
        return ! method_exists($ticket, 'isPublicOnly') || $ticket->isPublicOnly();
    }


    /**
     * Gets the ticket price as displayed by the ticket selector on the event page.
     *
     * Mirrors TicketSelectorRowStandard::setTicketPriceDetails(), since Calendar+ is released separately
     * from EE core and has no minimum core version. Any changes there should also be made here.
     *
     * The price is calculated from the ticket's EE_Price objects because TKT_price depends on how the ticket
     * was saved. The ticket price calculator saves the total with taxes, and the legacy editor saves the subtotal.
     *
     * @param EE_Ticket $ticket
     * @return float
     * @throws EE_Error
     * @throws ReflectionException
     */
    private function displayedPrice(EE_Ticket $ticket): float
    {
        $price = (EE_Config::instance()->tax_settings->prices_displayed_including_taxes ?? true)
            ? $ticket->get_ticket_total_with_taxes()
            : $ticket->get_ticket_subtotal();
        $min   = $ticket->min();
        // for ticket bundles, min and max qty are the same
        if ($min !== 0 && $min === $ticket->max()) {
            $price *= $min;
        }
        $price = (float) apply_filters('FHEE__ticket_selector_chart_template__ticket_price', $price, $ticket);
        // round to the currency's decimal places so the price filters compare the same amount that gets displayed,
        // ie: 13.53125 displays as 13.53 and needs to pass a max price of 13.53
        return round($price, EE_Currency_Config::getCurrencyConfig()->dec_plc);
    }


    private function logError(Exception $exception): void
    {
        if (WP_DEBUG) {
            error_log($exception->getMessage());
        }
    }
}
