<?php

declare(strict_types=1);

namespace EventEspresso\CalendarPlus\tests\api;

use EventEspresso\CalendarPlus\api\PriceRange;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for PriceRange::normalize(), shared by the shortcode and the Anywhere
 * embed generator.
 *
 * @group price-range
 */
class PriceRangeTest extends TestCase
{
    /**
     * @dataProvider rangeProvider
     */
    public function testNormalize(string $min, string $max, array $expected): void
    {
        $this->assertSame($expected, PriceRange::normalize($min, $max));
    }

    public function rangeProvider(): array
    {
        return [
            'negative min is dropped'           => ['-5', '100', ['min' => '', 'max' => '100']],
            'negative max is dropped'           => ['10', '-5', ['min' => '10', 'max' => '']],
            'non-numeric min is dropped'        => ['abc', '50', ['min' => '', 'max' => '50']],
            'scientific notation is normalized' => ['1e3', '2000', ['min' => '1000', 'max' => '2000']],
            'leading whitespace is trimmed'     => [' 5', '10', ['min' => '5', 'max' => '10']],
            'equal bounds are kept'             => ['20', '20', ['min' => '20', 'max' => '20']],
            'empty min, valid max'              => ['', '50', ['min' => '', 'max' => '50']],
            'valid min, empty max'              => ['30', '', ['min' => '30', 'max' => '']],
            'both empty'                        => ['', '', ['min' => '', 'max' => '']],
            'inverted range drops both'         => ['80', '20', ['min' => '', 'max' => '']],
        ];
    }
}
