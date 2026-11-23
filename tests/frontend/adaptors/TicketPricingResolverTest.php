<?php

declare(strict_types=1);

namespace EventEspresso\CalendarPlus\tests\frontend\adaptors;

use EE_Config;
use EE_Currency_Config;
use EE_Datetime;
use EE_Ticket;
use Exception;
use EventEspresso\CalendarPlus\frontend\adaptors\TicketPricingResolver;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit coverage for TicketPricingResolver's price calculation: empty/filtered tickets,
 * trashed and non-public tickets, the ticket selector's tax setting, bundle multiplier
 * and price filter, rounding, and a thrown exception.
 *
 * @group ticket-pricing
 */
class TicketPricingResolverTest extends TestCase
{
    private const PRICE_FILTER = 'FHEE__ticket_selector_chart_template__ticket_price';

    private TicketPricingResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TicketPricingResolver();
        $this->setPricesIncludeTaxes(true);
        $this->setShowExpiredTickets(true);
        EE_Currency_Config::$next_config = null;
    }


    protected function tearDown(): void
    {
        remove_all_filters(self::PRICE_FILTER);
        $this->setPricesIncludeTaxes(true);
        $this->setShowExpiredTickets(true);
        EE_Currency_Config::$next_config = null;
    }


    private function setPricesIncludeTaxes(bool $include_taxes): void
    {
        EE_Config::instance()->tax_settings->prices_displayed_including_taxes = $include_taxes;
    }


    private function setShowExpiredTickets(bool $show_expired_tickets): void
    {
        EE_Config::instance()->template_settings->EED_Ticket_Selector->show_expired_tickets = $show_expired_tickets;
    }


    /**
     * @return EE_Ticket mock ticket with an unlimited (-1) max qty, which is not a bundle
     */
    private function ticket(float $subtotal, float $total_with_taxes, int $min = 0, int $max = -1, bool $public = true)
    {
        $ticket = $this->createMock(EE_Ticket::class);
        $ticket->method('get_ticket_subtotal')->willReturn($subtotal);
        $ticket->method('get_ticket_total_with_taxes')->willReturn($total_with_taxes);
        $ticket->method('min')->willReturn($min);
        $ticket->method('max')->willReturn($max);
        $ticket->method('isPublicOnly')->willReturn($public);
        return $ticket;
    }


    private function datetimeWith(array $tickets)
    {
        $datetime = $this->createMock(EE_Datetime::class);
        $datetime->method('tickets')->willReturn($tickets);
        return $datetime;
    }


    private function assertPricing(float $min, float $max, array $tickets): void
    {
        $this->assertSame(
            ['minPrice' => $min, 'maxPrice' => $max],
            $this->resolver->forDatetime($this->datetimeWith($tickets))
        );
    }


    public function testReturnsNullWhenThereAreNoTickets(): void
    {
        $this->assertNull($this->resolver->forDatetime($this->datetimeWith([])));
    }


    public function testExcludesNonEETicketResults(): void
    {
        $this->assertPricing(25.0, 25.0, [$this->ticket(25.0, 25.0), new stdClass(), null]);
    }


    public function testReturnsNullWhenTicketsFilterToNothing(): void
    {
        $this->assertNull($this->resolver->forDatetime($this->datetimeWith([new stdClass()])));
    }


    public function testQueriesOnlyNonTrashedTickets(): void
    {
        $datetime = $this->createMock(EE_Datetime::class);
        $datetime->expects($this->once())
            ->method('tickets')
            ->with([['TKT_deleted' => false]])
            ->willReturn([$this->ticket(10.0, 10.0)]);

        $this->resolver->forDatetime($datetime);
    }


    public function testQueriesOnlyTicketsStillOnSaleWhenExpiredTicketsAreHidden(): void
    {
        $this->setShowExpiredTickets(false);
        $now = time();

        $datetime = $this->createMock(EE_Datetime::class);
        $datetime->expects($this->once())
            ->method('tickets')
            ->with(
                $this->callback(
                    static fn(array $query_params): bool => $query_params[0]['TKT_deleted'] === false
                        && $query_params[0]['TKT_end_date'][0] === '>'
                        && $query_params[0]['TKT_end_date'][1] >= $now
                )
            )
            ->willReturn([$this->ticket(25.0, 25.0)]);

        $this->resolver->forDatetime($datetime);
    }


    public function testEqualPricesReturnTheSameMinAndMax(): void
    {
        $this->assertPricing(15.0, 15.0, [$this->ticket(15.0, 15.0), $this->ticket(15.0, 15.0)]);
    }


    public function testDifferingPricesReturnTheirMinAndMax(): void
    {
        $this->assertPricing(5.0, 92.0, [$this->ticket(5.0, 5.0), $this->ticket(92.0, 92.0)]);
    }


    public function testFreeTicketStaysFree(): void
    {
        $this->assertPricing(0.0, 0.0, [$this->ticket(0.0, 0.0)]);
    }


    public function testReturnsNullWhenTicketsThrows(): void
    {
        $datetime = $this->createMock(EE_Datetime::class);
        $datetime->method('tickets')->willThrowException(new Exception('DB unavailable'));

        $this->assertNull($this->resolver->forDatetime($datetime));
    }


    public function testUsesTotalWithTaxesWhenPricesIncludeTaxes(): void
    {
        $this->assertPricing(110.0, 110.0, [$this->ticket(100.0, 110.0)]);
    }


    public function testUsesSubtotalWhenPricesExcludeTaxes(): void
    {
        $this->setPricesIncludeTaxes(false);

        $this->assertPricing(100.0, 100.0, [$this->ticket(100.0, 110.0)]);
    }


    public function testTaxSettingAppliesToEveryTicketInTheRange(): void
    {
        $this->setPricesIncludeTaxes(false);

        $this->assertPricing(50.0, 100.0, [$this->ticket(50.0, 55.0), $this->ticket(100.0, 110.0)]);
    }


    public function testMultipliesBundlePriceByItsQuantity(): void
    {
        $this->assertPricing(200.0, 200.0, [$this->ticket(50.0, 50.0, 4, 4)]);
    }


    public function testBundleUsesSubtotalWhenPricesExcludeTaxes(): void
    {
        $this->setPricesIncludeTaxes(false);

        $this->assertPricing(200.0, 200.0, [$this->ticket(50.0, 55.0, 4, 4)]);
    }


    public function testDifferentMinAndMaxIsNotABundle(): void
    {
        $this->assertPricing(50.0, 50.0, [$this->ticket(50.0, 50.0, 2, 10)]);
    }


    public function testZeroMinAndMaxIsNotABundle(): void
    {
        $this->assertPricing(50.0, 50.0, [$this->ticket(50.0, 50.0, 0, 0)]);
    }


    public function testAppliesTheTicketSelectorPriceFilterAfterTheBundleMultiplier(): void
    {
        add_filter(self::PRICE_FILTER, static fn($price) => $price - 25.0);

        $this->assertPricing(175.0, 175.0, [$this->ticket(50.0, 50.0, 4, 4)]);
    }


    public function testPassesTheTicketToThePriceFilter(): void
    {
        $ticket   = $this->ticket(50.0, 50.0);
        $received = null;
        add_filter(
            self::PRICE_FILTER,
            static function ($price, $filter_ticket) use (&$received) {
                $received = $filter_ticket;
                return $price;
            },
            10,
            2
        );

        $this->resolver->forDatetime($this->datetimeWith([$ticket]));

        $this->assertSame($ticket, $received);
    }


    public function testRoundsToTheAmountThatVisitorsRead(): void
    {
        // $12.50 + 8.25% tax
        $this->assertPricing(13.53, 13.53, [$this->ticket(12.5, 13.53125)]);
    }


    public function testRoundsToTheCurrencyDecimalPlaces(): void
    {
        $currency                        = new EE_Currency_Config();
        $currency->dec_plc               = 0;
        EE_Currency_Config::$next_config = $currency;

        $this->assertPricing(14.0, 14.0, [$this->ticket(12.5, 13.53125)]);
    }


    public function testExcludesNonPublicTicketsFromTheRange(): void
    {
        $this->assertPricing(100.0, 100.0, [
            $this->ticket(20.0, 20.0, 0, -1, false),
            $this->ticket(100.0, 100.0),
        ]);
    }


    public function testReturnsNullWhenNoTicketIsPublic(): void
    {
        $this->assertNull(
            $this->resolver->forDatetime($this->datetimeWith([$this->ticket(20.0, 20.0, 0, -1, false)]))
        );
    }
}
