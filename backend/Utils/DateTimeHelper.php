<?php

namespace App\Utils;

class DateTimeHelper
{

    // Convert any date-time string to UTC ISO 8601 format.
    public static function toUtcIso(string $dateTime): string
    {
        $dt = new \DateTime($dateTime);
        $dt->setTimezone(new \DateTimeZone('UTC'));
        return $dt->format('Y-m-d\TH:i:s\Z');
    }


    //  Recursively convert all date-time fields in an array.
    public static function convertTimestamps(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = self::toUtcIso($data[$field]);
            }
        }
        return $data;
    }
}