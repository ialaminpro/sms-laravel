<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Support;

use Acolyte\SmsLaravel\Data\SmsSegments;
use InvalidArgumentException;

final class SmsSegmentCalculator
{
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM_EXTENDED = '^{}\\[~]|€';

    public function calculate(string $message): SmsSegments
    {
        $characters = $this->characters($message);

        if ($characters === []) {
            return new SmsSegments('GSM-7', 0, 0, 0, 160);
        }

        $septets = 0;

        foreach ($characters as $character) {
            if (str_contains(self::GSM_BASIC, $character)) {
                $septets++;

                continue;
            }

            if (str_contains(self::GSM_EXTENDED, $character)) {
                $septets += 2;

                continue;
            }

            return $this->ucs2($characters);
        }

        $perSegment = $septets <= 160 ? 160 : 153;

        return new SmsSegments('GSM-7', count($characters), $septets, (int) ceil($septets / $perSegment), $perSegment);
    }

    /**
     * @return list<string>
     */
    private function characters(string $message): array
    {
        if (preg_match('//u', $message) !== 1) {
            throw new InvalidArgumentException('The SMS body must contain valid UTF-8.');
        }

        $characters = preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY);

        return $characters === false ? [] : $characters;
    }

    /**
     * UCS-2 limits are measured in UTF-16 code units. Non-BMP characters such as
     * emoji occupy a surrogate pair and therefore consume two units.
     *
     * @param  list<string>  $characters
     */
    private function ucs2(array $characters): SmsSegments
    {
        $units = 0;

        foreach ($characters as $character) {
            $units += strlen($character) === 4 ? 2 : 1;
        }

        $perSegment = $units <= 70 ? 70 : 67;

        return new SmsSegments('UCS-2', count($characters), $units, (int) ceil($units / $perSegment), $perSegment);
    }
}
