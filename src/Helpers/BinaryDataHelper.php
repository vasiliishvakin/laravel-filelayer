<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Helpers;

use RuntimeException;
use Vaskiq\LaravelFileLayer\Data\Base64FileData;

class BinaryDataHelper
{
    public static function decodeBase64(string $base64String): Base64FileData
    {
        [$data, $type] = self::parseBase64String($base64String);
        $binaryData = base64_decode($data, true);

        if ($binaryData === false) {
            throw new RuntimeException('Failed to decode base64 string.');
        }

        return Base64FileData::from($binaryData, $type);
    }

    private static function parseBase64String(string $string): array
    {
        [$type, $data] = explode(';', $string);
        [, $data] = explode(',', $data);
        [, $type] = explode(':', $type);

        return [$data, $type];
    }
}
