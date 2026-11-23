<?php

namespace EventEspresso\CalendarPlus\api;

use EE_Currency_Config;
use Exception;

/**
 * CurrencyData
 *
 * Resolves the currency payload from EE core's currency config, mapping its field
 * names to the shape the frontend expects. Falls back to a default (USD) shape
 * when EE core isn't loaded or the config can't be resolved.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\api
 * @author      Mohsin Sabir
 * @since       {VID}
 */
class CurrencyData
{
    public function resolve(): array
    {
        if (! class_exists('EE_Currency_Config')) {
            return $this->defaultCurrency();
        }

        try {
            $currency = EE_Currency_Config::getCurrencyConfig();
        } catch (Exception $exception) {
            error_log(sprintf('Calendar Plus: failed to resolve EE currency config: %s', $exception->getMessage()));
            return $this->defaultCurrency();
        }

        return [
            'code'               => $currency->code,
            'sign'               => $currency->sign,
            'signB4'             => (bool) $currency->sign_b4,
            'decimalPlaces'      => $currency->dec_plc,
            'decimalMark'        => $currency->dec_mrk,
            'thousandsSeparator' => $currency->thsnds,
        ];
    }


    public function defaultCurrency(): array
    {
        return [
            'code'               => 'USD',
            'sign'               => '$',
            'signB4'             => true,
            'decimalPlaces'      => 2,
            'decimalMark'        => '.',
            'thousandsSeparator' => ',',
        ];
    }
}
