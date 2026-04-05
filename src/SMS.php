<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel;

use Acolyte\SmsLaravel\Data\SmsMessage;
use InvalidArgumentException;

/**
 * Compatibility adapter for the 1.x array API.
 *
 * @deprecated Use Acolyte\SmsLaravel\Facades\Sms with SmsMessage instead.
 */
final class SMS
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function send(array $parameters = []): string
    {
        $mobile = $parameters['mobile'] ?? null;
        $body = $parameters['smsText'] ?? null;

        if (! is_string($mobile) || ! is_string($body)) {
            throw new InvalidArgumentException('Legacy SMS parameters require string mobile and smsText values.');
        }

        $sender = isset($parameters['mask']) && is_string($parameters['mask']) ? $parameters['mask'] : null;
        $reference = isset($parameters['campaign']) && is_string($parameters['campaign']) ? $parameters['campaign'] : null;
        $result = app(SmsManager::class)->send(new SmsMessage($mobile, $body, $sender, $reference));

        return json_encode([
            'status' => $result->successful ? 'success' : 'failed',
            'msg' => $result->successful ? 'Successfully Delivered' : $result->errorMessage,
            'provider_message_id' => $result->providerMessageId,
            'error_code' => $result->errorCode,
        ], JSON_THROW_ON_ERROR);
    }
}
