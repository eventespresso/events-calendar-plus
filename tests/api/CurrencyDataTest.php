<?php

declare(strict_types=1);

namespace EventEspresso\CalendarPlus\tests\api;

use EE_Currency_Config;
use EventEspresso\CalendarPlus\api\CurrencyData;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for CurrencyData's EE-core field mapping and its two fallbacks.
 *
 * Not covered: the "EE_Currency_Config class doesn't exist" branch. The bootstrap
 * defines a fake EE_Currency_Config for the mapping test below, and a class can't
 * be un-defined afterward. That branch returns the same defaultCurrency() the
 * thrown-exception test already covers.
 *
 * @group currency
 */
class CurrencyDataTest extends TestCase
{
    protected function tearDown(): void
    {
        EE_Currency_Config::$next_config  = null;
        EE_Currency_Config::$should_throw = false;
    }


    public function testDefaultCurrencyShape(): void
    {
        $this->assertSame(
            [
                'code'               => 'USD',
                'sign'               => '$',
                'signB4'             => true,
                'decimalPlaces'      => 2,
                'decimalMark'        => '.',
                'thousandsSeparator' => ',',
            ],
            (new CurrencyData())->defaultCurrency()
        );
    }


    public function testResolveMapsEECurrencyConfigFields(): void
    {
        $config             = new EE_Currency_Config();
        $config->code       = 'EUR';
        $config->sign       = '€';
        $config->sign_b4    = false;
        $config->dec_plc    = 3;
        $config->dec_mrk    = ',';
        $config->thsnds     = '.';
        EE_Currency_Config::$next_config = $config;

        $this->assertSame(
            [
                'code'               => 'EUR',
                'sign'               => '€',
                'signB4'             => false,
                'decimalPlaces'      => 3,
                'decimalMark'        => ',',
                'thousandsSeparator' => '.',
            ],
            (new CurrencyData())->resolve()
        );
    }


    public function testResolveCoercesSignB4ToBool(): void
    {
        // EE core types sign_b4 as bool|int; a legacy int 0/1 must still normalize to bool.
        $config          = new EE_Currency_Config();
        $config->sign_b4 = 0;
        EE_Currency_Config::$next_config = $config;

        $this->assertSame(false, (new CurrencyData())->resolve()['signB4']);
    }


    public function testResolveFallsBackToDefaultWhenCurrencyConfigThrows(): void
    {
        EE_Currency_Config::$should_throw = true;

        $this->assertSame(
            (new CurrencyData())->defaultCurrency(),
            (new CurrencyData())->resolve()
        );
    }
}
