<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Unit;

use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SmsMessageAndResultTest extends TestCase
{
    public function test_message_normalizes_phone_whitespace_and_optional_values(): void
    {
        $message = new SmsMessage(' +49 123 456 ', 'Hello', ' Sender ', ' ref-1 ');

        self::assertSame('+49123456', $message->to);
        self::assertSame('Sender', $message->sender);
        self::assertSame('ref-1', $message->clientReference);
    }

    public function test_empty_number_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('recipient');

        new SmsMessage('   ', 'Hello');
    }

    public function test_empty_body_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('body');

        new SmsMessage('+49123', '');
    }

    public function test_invalid_utf8_body_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SmsMessage('+49123', "\xB1\x31");
    }

    public function test_result_named_constructors_keep_the_contract_consistent(): void
    {
        $success = SmsResult::success('provider-1', ['accepted' => true]);
        $failure = SmsResult::failure('rate_limited', 'Try later.');

        self::assertTrue($success->successful);
        self::assertSame('provider-1', $success->providerMessageId);
        self::assertNull($success->errorCode);
        self::assertFalse($failure->successful);
        self::assertSame('rate_limited', $failure->errorCode);
        self::assertNull($failure->providerMessageId);
    }
}
