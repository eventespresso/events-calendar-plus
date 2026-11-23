<?php

namespace EventEspresso\CalendarPlus\api;

/**
 * PriceRange
 *
 * Validates and normalizes a raw min/max price pair from a shortcode attribute
 * or request param. Each bound is kept only if it is a non-negative number;
 * an inverted range (min > max) discards both bounds rather than one.
 *
 * @package     Event Espresso
 * @subpackage  EventEspresso\CalendarPlus\api
 * @author      Mohsin Sabir
 * @since       {VID}
 */
class PriceRange
{
    /**
     * @param string $min
     * @param string $max
     * @return array{min: string, max: string} Each bound is a canonical numeric string
     * (e.g. "5", "1000"), or '' when invalid/cleared.
     */
    public static function normalize(string $min, string $max): array
    {
        $min = self::normalizeBound($min);
        $max = self::normalizeBound($max);

        if ($min !== '' && $max !== '' && (float) $min > (float) $max) {
            $min = '';
            $max = '';
        }

        return ['min' => $min, 'max' => $max];
    }


    private static function normalizeBound(string $value): string
    {
        return is_numeric($value) && (float) $value >= 0 ? (string) (float) $value : '';
    }
}
