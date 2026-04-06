<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Unit;

use Acolyte\SmsLaravel\Support\SmsSegmentCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SmsSegmentCalculatorTest extends TestCase
{
    private SmsSegmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SmsSegmentCalculator;
    }

    /**
     * @return iterable<string, array{string, string, int, int, int}>
     */
    public static function messages(): iterable
    {
        yield 'empty' => ['', 'GSM-7', 0, 0, 0];
        yield 'gsm single' => [str_repeat('a', 160), 'GSM-7', 160, 160, 1];
        yield 'gsm multipart' => [str_repeat('a', 161), 'GSM-7', 161, 161, 2];
        yield 'gsm extended single' => [str_repeat('^', 80), 'GSM-7', 80, 160, 1];
        yield 'gsm extended multipart' => [str_repeat('^', 81), 'GSM-7', 81, 162, 2];
        yield 'unicode single' => [str_repeat('হ', 70), 'UCS-2', 70, 70, 1];
        yield 'unicode multipart' => [str_repeat('হ', 71), 'UCS-2', 71, 71, 2];
        yield 'emoji surrogate pairs' => [str_repeat('🙂', 36), 'UCS-2', 36, 72, 2];
    }

    #[DataProvider('messages')]
    public function test_calculates_encoding_units_and_segments(
        string $body,
        string $encoding,
        int $characters,
        int $units,
        int $segments,
    ): void {
        $result = $this->calculator->calculate($body);

        self::assertSame($encoding, $result->encoding);
        self::assertSame($characters, $result->characters);
        self::assertSame($units, $result->units);
        self::assertSame($segments, $result->segments);
    }
}
